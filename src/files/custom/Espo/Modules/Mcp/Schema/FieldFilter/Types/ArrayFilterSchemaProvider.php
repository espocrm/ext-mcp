<?php
/**LICENSE**/

namespace Espo\Modules\Mcp\Schema\FieldFilter\Types;

use Espo\Core\Select\Where\Item\Type;
use Espo\Core\Utils\Language;
use Espo\Modules\Mcp\Schema\FieldFilter\FieldFilterSchemaProvider;
use Espo\Modules\Mcp\Tools\JsonSchema\ConstSchema;
use Espo\Modules\Mcp\Tools\JsonSchema\GroupKeyword;
use Espo\Modules\Mcp\Tools\JsonSchema\GroupSchema;
use Espo\Modules\Mcp\Tools\JsonSchema\Type\ArrayType;
use Espo\Modules\Mcp\Tools\JsonSchema\Type\ObjectType;
use Espo\Modules\Mcp\Tools\JsonSchema\Type\StringType;
use Espo\ORM\Defs;
use Espo\Tools\OpenApi\Util\EnumOptionsProvider;

/**
 * @noinspection PhpUnused
 */
class ArrayFilterSchemaProvider implements FieldFilterSchemaProvider
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
        $value = new ArrayType(
            items: new StringType(),
            uniqueItems: true,
            description: 'Options.',
        );

        $filedDefs = $this->ormDefs->getEntity($entityType)->getField($field);
        $options = $this->enumOptionsProvider->get($filedDefs);

        if ($options) {
            $value = new ArrayType(
                items: GroupSchema::createAnyOf(
                    schemas: [
                        ...array_map(function (string $it) use ($entityType, $field) {
                            return new ConstSchema(
                                value: $it,
                                title: $this->defaultLanguage->translateOption($it, $field, $entityType),
                            );
                        }, $options)
                    ],
                ),
                uniqueItems: true,
                description: 'Options.',
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
                            new ConstSchema(
                                value: Type::ARRAY_ANY_OF,
                                description: 'At least one provided values is present.',
                            ),
                            new ConstSchema(
                                value: Type::ARRAY_ALL_OF,
                                description: 'All provided values are present.',
                            ),
                            new ConstSchema(
                                value: Type::ARRAY_NONE_OF,
                                description: 'None of provided values is present.',
                            ),
                        ],
                    ),
                    'value' => $value,
                ],
                required: [
                    'attribute',
                    'type',
                    'value',
                ],
                description: "'$label' field filter. Field type is 'Array'.",
            ),
            new ObjectType(
                properties: [
                    'attribute' => new ConstSchema(value: $field),
                    'type' => new GroupSchema(
                        keyword: GroupKeyword::anyOff,
                        schemas: [
                            new ConstSchema(value: Type::ARRAY_IS_EMPTY),
                            new ConstSchema(value: Type::ARRAY_IS_NOT_EMPTY),
                        ],
                    ),
                ],
                required: [
                    'attribute',
                    'type',
                ],
                description: "'$label' field filter checking if the value is empty or not.",
            ),
        ];
    }
}
