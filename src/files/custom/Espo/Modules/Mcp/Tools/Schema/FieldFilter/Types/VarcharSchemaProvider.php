<?php
/**LICENSE**/

namespace Espo\Modules\Mcp\Tools\Schema\FieldFilter\Types;

use Espo\Core\Select\Where\Item\Type;
use Espo\Core\Utils\Language;
use Espo\Modules\Mcp\Tools\Schema\FieldFilter\SchemaProvider;
use Espo\Modules\Mcp\Tools\JsonSchema\ConstSchema;
use Espo\Modules\Mcp\Tools\JsonSchema\EnumSchema;
use Espo\Modules\Mcp\Tools\JsonSchema\GroupKeyword;
use Espo\Modules\Mcp\Tools\JsonSchema\GroupSchema;
use Espo\Modules\Mcp\Tools\JsonSchema\Type\ObjectType;
use Espo\Modules\Mcp\Tools\JsonSchema\Type\StringType;
use Espo\Modules\Mcp\Tools\Schema\FieldFilter\SchemaProvider\Params;
use Espo\Modules\Mcp\Tools\Schema\Util\EnumOptionsProvider;
use Espo\ORM\Defs;
use Espo\ORM\Defs\Params\FieldParam;

/**
 * @noinspection PhpUnused
 */
class VarcharSchemaProvider implements SchemaProvider
{
    public function __construct(
        private EnumOptionsProvider $enumOptionsProvider,
        private Language $defaultLanguage,
        private Defs $ormDefs,
    ) {}

    /**
     * @return ObjectType[]
     */
    public function get(Params $params): array
    {
        $entityType = $params->entityType;
        $field = $params->field;

        $value = new StringType(
            description: "Query string.",
        );

        $fieldDefs = $this->ormDefs->getEntity($entityType)->getField($field);
        $options = $this->enumOptionsProvider->get($fieldDefs);

        if ($options) {
            $value = GroupSchema::createAnyOf(
                schemas: [
                    new StringType(
                        description: "Query string.",
                    ),
                    new EnumSchema(
                        values: $options,
                    ),
                ],
                description: "Query string.",
            );
        }

        $label = $this->defaultLanguage->translateLabel($field, 'fields', $entityType);

        return [
            new ObjectType(
                properties: [
                    'attribute' => new ConstSchema(value: $field),
                    'type' => new GroupSchema(
                        keyword: GroupKeyword::anyOf,
                        schemas: [
                            new ConstSchema(value: Type::EQUALS),
                            new ConstSchema(value: Type::STARTS_WITH),
                            new ConstSchema(value: Type::CONTAINS),
                        ],
                    ),
                    'value' => $value,
                ],
                required: [
                    'attribute',
                    'type',
                    'value',
                ],
                description: "'$label' field filter.",
            ),
            ...(
                !$fieldDefs->getParam(FieldParam::REQUIRED) ?
                    [
                        new ObjectType(
                            properties: [
                                'attribute' => new ConstSchema(value: $field),
                                'type' => new GroupSchema(
                                    keyword: GroupKeyword::anyOf,
                                    schemas: [
                                        new ConstSchema(value: Type::IS_NOT_NULL),
                                        new ConstSchema(value: Type::IS_NULL),
                                    ],
                                ),
                            ],
                            required: [
                                'attribute',
                                'type',
                            ],
                            description: "'$label' field filter checking if the value is set or not.",
                        ),
                    ] : []
            ),
        ];
    }
}
