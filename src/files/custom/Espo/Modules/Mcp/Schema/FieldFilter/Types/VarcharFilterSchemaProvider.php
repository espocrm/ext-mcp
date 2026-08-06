<?php
/**LICENSE**/

namespace Espo\Modules\Mcp\Schema\FieldFilter\Types;

use Espo\Core\Select\Where\Item\Type;
use Espo\Core\Utils\Language;
use Espo\Modules\Mcp\Schema\FieldFilter\FieldFilterSchemaProvider;
use Espo\Modules\Mcp\Tools\JsonSchema\ConstSchema;
use Espo\Modules\Mcp\Tools\JsonSchema\EnumSchema;
use Espo\Modules\Mcp\Tools\JsonSchema\GroupKeyword;
use Espo\Modules\Mcp\Tools\JsonSchema\GroupSchema;
use Espo\Modules\Mcp\Tools\JsonSchema\Type\ObjectType;
use Espo\Modules\Mcp\Tools\JsonSchema\Type\StringType;
use Espo\ORM\Defs;
use Espo\Tools\OpenApi\Util\EnumOptionsProvider;

/**
 * @noinspection PhpUnused
 */
class VarcharFilterSchemaProvider implements FieldFilterSchemaProvider
{
    public function __construct(
        private EnumOptionsProvider $enumOptionsProvider,
        private Language $defaultLanguage,
        private Defs $ormDefs,
    ) {}

    /**
     * @return ObjectType[]
     */
    public function get(string $entityType, string $field): array
    {
        $value = new StringType(
            description: "Query string.",
        );

        $filedDefs = $this->ormDefs->getEntity($entityType)->getField($field);
        $options = $this->enumOptionsProvider->get($filedDefs);

        if ($options) {
            $value = new EnumSchema(
                values: [
                    new StringType(
                        description: "Query string.",
                    ),
                    ...$options,
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
                        keyword: GroupKeyword::anyOff,
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
            new ObjectType(
                properties: [
                    'attribute' => new ConstSchema(value: $field),
                    'type' => new GroupSchema(
                        keyword: GroupKeyword::anyOff,
                        schemas: [
                            new ConstSchema(value: Type::IS_NULL),
                            new ConstSchema(value: Type::IS_NOT_NULL),
                        ],
                    ),
                ],
                required: [
                    'attribute',
                    'type',
                ],
                description: "'$label' field filter checking if value is empty or not.",
            ),
        ];
    }
}
