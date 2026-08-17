<?php
/**LICENSE**/

namespace Espo\Modules\Mcp\Tools\Feature\Create;

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
use Espo\Modules\Mcp\Tools\Schema\Field\FieldSchemaProvider\Action;
use Espo\Modules\Mcp\Tools\Schema\Field\FieldSchemaProvider\Params as FieldSchemaProviderParams;
use Espo\Modules\Mcp\Tools\Schema\Field\SchemaProviderFactory as FieldSchemaProviderFactory;
use Espo\ORM\Defs;
use Espo\ORM\Defs\Params\FieldParam;
use Espo\ORM\Name\Attribute;

/**
 * @implements ToolDefinitionProvider<CreateData>
 */
class CreateToolDefinitionProvider implements ToolDefinitionProvider
{
    private const string DESCRIPTION = "Creates '{scopeName}' record. Entity type: `{entityType}`.";

    public function __construct(
        private Language $defaultLanguage,
        private FieldSchemaProviderFactory $fieldSchemaProviderFactory,
        private Acl $acl,
        private Defs $ormDefs,
    ) {}

    public function get(Data $data): Tool
    {
        if (!$this->acl->tryCheck($data->entityType, Acl\Table::ACTION_CREATE)) {
            throw new NoUserAccess("No 'create' access to '$data->entityType'.");
        }

        return new Tool(
            name: 'Create.' . $data->entityType,
            inputSchema: new RootObjectSchema($this->prepareInputSchema($data)),
            outputSchema: new RootSchema($this->prepareOutputSchema($data)),
            description: $this->getDescription($data),
        );
    }

    /**
     * @throws UnsupportedFeatureValue
     */
    private function prepareInputSchema(CreateData $data): ObjectType
    {
        $properties = [];
        $suppress = [];

        $writeFields = array_map(fn ($it) => $it->name, $data->writeFields);

        $entityDefs = $this->ormDefs->getEntity($data->entityType);

        foreach ($writeFields as $field) {
            if (
                !$this->acl->checkField($data->entityType, $field, Acl\Table::ACTION_EDIT) ||
                in_array($field, $suppress)
            ) {
                continue;
            }

            if ($entityDefs->tryGetField($field)?->getParam(FieldParam::READ_ONLY)) {
                continue;
            }

            $provider = $this->fieldSchemaProviderFactory->create($data->entityType, $field);

            $params = new FieldSchemaProviderParams(
                entityType: $data->entityType,
                field: $field,
                action: Action::Create,
            );

            $result = $provider->get($params);

            $properties = array_merge($properties, $result->properties);
            $suppress = array_merge($suppress, $result->suppress);
        }

        return new ObjectType(
            properties: [
                'record' => new ObjectType(
                    properties: $properties,
                    additionalProperties: false,
                    description: "Record values.",
                ),
            ],
            additionalProperties: false,
        );
    }

    /**
     * @throws UnsupportedFeatureValue
     */
    private function prepareOutputSchema(CreateData $data): Schema
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
                    description: "Record. To fetch other fields, use the `Read.$data->entityType` tool.",
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
                                    description: "No 'read' or 'edit' access to the record.",
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

    private function getDescription(CreateData $data): string
    {
        return strtr(self::DESCRIPTION, [
            'scopeName' => $this->defaultLanguage->translateLabel($data->entityType, 'scopeNames'),
            'entityType' => $data->entityType,
        ]);
    }
}
