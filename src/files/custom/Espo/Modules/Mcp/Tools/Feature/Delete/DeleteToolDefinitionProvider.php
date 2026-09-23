<?php
/**LICENSE**/

namespace Espo\Modules\Mcp\Tools\Feature\Delete;

use Espo\Core\Acl;
use Espo\Core\Utils\Language;
use Espo\Modules\Mcp\Tools\Feature\Data;
use Espo\Modules\Mcp\Tools\Feature\Exceptions\NoUserAccess;
use Espo\Modules\Mcp\Tools\Feature\Exceptions\UnsupportedFeatureValue;
use Espo\Modules\Mcp\Tools\Feature\ToolDefinitionProvider;
use Espo\Modules\Mcp\Tools\JsonSchema\ConstSchema;
use Espo\Modules\Mcp\Tools\JsonSchema\GroupSchema;
use Espo\Modules\Mcp\Tools\JsonSchema\Schema;
use Espo\Modules\Mcp\Tools\JsonSchema\Type\ObjectType;
use Espo\Modules\Mcp\Tools\JsonSchema\Type\StringType;
use Espo\Modules\Mcp\Tools\Mcp\Schema\General\RootObjectSchema;
use Espo\Modules\Mcp\Tools\Mcp\Schema\General\RootSchema;
use Espo\Modules\Mcp\Tools\Mcp\Schema\Tool\Tool;
use Espo\Modules\Mcp\Tools\Mcp\Schema\Tool\ToolAnnotations;
use Espo\Modules\Mcp\Tools\Schema\Field\FieldSchemaProvider\Action;
use Espo\Modules\Mcp\Tools\Schema\Field\FieldSchemaProvider\Params as FieldSchemaProviderParams;
use Espo\Modules\Mcp\Tools\Schema\Field\SchemaProviderFactory as FieldSchemaProviderFactory;
use Espo\ORM\Name\Attribute;

/**
 * @implements ToolDefinitionProvider<DeleteData>
 */
class DeleteToolDefinitionProvider implements ToolDefinitionProvider
{
    private const string DESCRIPTION = "Deletes '{scopeName}' record. Entity type: `{entityType}`.";

    public function __construct(
        private Language $defaultLanguage,
        private FieldSchemaProviderFactory $fieldSchemaProviderFactory,
        private Acl $acl,
    ) {}

    public function get(Data $data): Tool
    {
        if (!$this->acl->tryCheck($data->entityType, Acl\Table::ACTION_DELETE)) {
            throw new NoUserAccess("No 'delete' access to '$data->entityType'.");
        }

        return new Tool(
            name: 'Delete.' . $data->entityType,
            inputSchema: new RootObjectSchema($this->prepareInputSchema()),
            outputSchema: new RootSchema($this->prepareOutputSchema($data)),
            description: $this->getDescription($data),
            annotations: new ToolAnnotations(
                readOnlyHint: false,
                destructiveHint: true,
                openWorldHint: false,
            ),
        );
    }

    private function prepareInputSchema(): ObjectType
    {
        $properties = [
            'id' => new StringType(
                description: "Record ID.",
            ),
        ];

        return new ObjectType(
            properties: $properties,
            required: ['id'],
            additionalProperties: false,
        );
    }

    /**
     * @throws UnsupportedFeatureValue
     */
    private function prepareOutputSchema(DeleteData $data): Schema
    {
        $properties = [];
        $suppress = [];

        $fields = [Attribute::ID];

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
                    description: "Deleted record.",
                ),
                'error' => new ObjectType(
                    properties: [
                        'message' => new StringType(
                            description: "Error message.",
                        ),
                        'code' => GroupSchema::createAnyOf(
                            schemas: [
                                new ConstSchema(
                                    value: 400,
                                    description: "Bad request.",
                                ),
                                new ConstSchema(
                                    value: 403,
                                    description: "No 'delete' access to the record. Or other access error.",
                                ),
                                new ConstSchema(
                                    value: 404,
                                    description: "Not found.",
                                ),
                                new ConstSchema(
                                    value: 409,
                                    description: "Conflict.",
                                ),
                            ],
                            description: 'Error code.',
                        ),
                    ],
                ),
            ],
        );
    }

    private function getDescription(DeleteData $data): string
    {
        return strtr(self::DESCRIPTION, [
            '{scopeName}' => $this->defaultLanguage->translateLabel($data->entityType, 'scopeNames'),
            '{entityType}' => $data->entityType,
        ]);
    }
}
