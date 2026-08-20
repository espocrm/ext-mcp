<?php
/**LICENSE**/

namespace Espo\Modules\Mcp\Tools\Feature\Read;

use Espo\Core\Acl;
use Espo\Core\Utils\Language;
use Espo\Modules\Mcp\Tools\Feature\Data;
use Espo\Modules\Mcp\Tools\Feature\Exceptions\NoUserAccess;
use Espo\Modules\Mcp\Tools\Feature\Exceptions\UnsupportedFeatureValue;
use Espo\Modules\Mcp\Tools\Feature\Find\FindData\Field;
use Espo\Modules\Mcp\Tools\Feature\ToolDefinitionProvider;
use Espo\Modules\Mcp\Tools\JsonSchema\ConstSchema;
use Espo\Modules\Mcp\Tools\JsonSchema\GroupSchema;
use Espo\Modules\Mcp\Tools\JsonSchema\Schema;
use Espo\Modules\Mcp\Tools\JsonSchema\Type\ArrayType;
use Espo\Modules\Mcp\Tools\JsonSchema\Type\ObjectType;
use Espo\Modules\Mcp\Tools\JsonSchema\Type\StringType;
use Espo\Modules\Mcp\Tools\Mcp\Schema\General\RootObjectSchema;
use Espo\Modules\Mcp\Tools\Mcp\Schema\General\RootSchema;
use Espo\Modules\Mcp\Tools\Mcp\Schema\Tool\Tool;
use Espo\Modules\Mcp\Tools\Schema\Field\FieldSchemaProvider\Action;
use Espo\Modules\Mcp\Tools\Schema\Field\FieldSchemaProvider\Params as FieldSchemaProviderParams;
use Espo\Modules\Mcp\Tools\Schema\Field\SchemaProviderFactory as FieldSchemaProviderFactory;
use Espo\ORM\Name\Attribute;

/**
 * @implements ToolDefinitionProvider<ReadData>
 */
class ReadToolDefinitionProvider implements ToolDefinitionProvider
{
    private const string DESCRIPTION = "Fetches '{scopeName}' record by ID. Entity type: `{entityType}`.";

    private const string ID_DESCRIPTION = "Record ID.";

    private const string SELECT_DESCRIPTION = "What fields to fetch. " .
        "If omitted, all fields from the output schema are fetched.";

    public function __construct(
        private Language $defaultLanguage,
        private FieldSchemaProviderFactory $fieldSchemaProviderFactory,
        private Acl $acl,
    ) {}

    public function get(Data $data): Tool
    {
        if (!$this->acl->tryCheck($data->entityType, Acl\Table::ACTION_READ)) {
            throw new NoUserAccess("No access to '$data->entityType'.");
        }

        return new Tool(
            name: 'Read.' . $data->entityType,
            inputSchema: new RootObjectSchema($this->prepareInputSchema($data)),
            outputSchema: new RootSchema($this->prepareOutputSchema($data)),
            description: $this->getDescription($data),
        );
    }

    private function prepareInputSchema(ReadData $data): ObjectType
    {
        $properties = [
            'id' => new StringType(
                description: self::ID_DESCRIPTION,
            ),
            'selectFields' => $this->getSelectFieldsSchema($data),
        ];

        return new ObjectType(
            properties: $properties,
            required: ['id'],
            additionalProperties: false,
        );
    }

    private function getSelectFieldsSchema(ReadData $data): Schema
    {
        return new ArrayType(
            items: GroupSchema::createAnyOf(
                schemas: array_map(function ($field) use ($data) {
                    return new ConstSchema(
                        value: $field->name,
                        title: $this->defaultLanguage->translateLabel($field->name, 'fields', $data->entityType),
                        description: $field->description,
                    );
                }, $this->filterFields($data->selectFields, $data->entityType))
            ),
            description: self::SELECT_DESCRIPTION,
        );
    }

    /**
     * @param Field[] $fields
     * @return Field[]
     */
    private function filterFields(array $fields, string $entityType): array
    {
        $fields = array_filter($fields, function ($field) use ($entityType) {
            return $this->acl->checkField($entityType, $field->name);
        });

        return array_values($fields);
    }

    /**
     * @throws UnsupportedFeatureValue
     */
    private function prepareOutputSchema(ReadData $data): Schema
    {
        $properties = [];
        $suppress = [];

        $selectFields = array_map(fn ($it) => $it->name, $data->selectFields);

        $fields = [Attribute::ID, ...$selectFields];

        foreach ($fields as $field) {
            if (
                !$this->acl->checkField($data->entityType, $field) ||
                in_array($field, $suppress)
            ) {
                continue;
            }

            $provider = $this->fieldSchemaProviderFactory->create($data->entityType, $field);

            $params = new FieldSchemaProviderParams(
                entityType: $data->entityType,
                field: $field,
                action: Action::Read,
            );

            $result = $provider->get($params);

            $properties = array_merge($properties, $result->properties);
            $suppress = array_merge($suppress, $result->suppress);
        }

        return new ObjectType(
            properties: [
                'record' => new ObjectType(
                    properties: $properties,
                    description: "Record.",
                ),
                'error' => new ObjectType(
                    properties: [
                        'message' => new StringType(
                            description: "Error message.",
                        ),
                        'code' => GroupSchema::createAnyOf(
                            schemas: [
                                new ConstSchema(
                                    value: 404,
                                    description: "Record not found.",
                                ),
                                new ConstSchema(
                                    value: 403,
                                    description: "No access to the record.",
                                ),
                            ],
                            description: 'Error code.',
                        ),
                    ],
                ),
            ],
        );
    }

    private function getDescription(ReadData $data): string
    {
        return strtr(self::DESCRIPTION, [
            'scopeName' => $this->defaultLanguage->translateLabel($data->entityType, 'scopeNames'),
            'entityType' => $data->entityType,
        ]);
    }
}
