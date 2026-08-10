<?php
/**LICENSE**/

namespace Espo\Modules\Mcp\Tools\Schema\FieldFilter\Types;

use Espo\Core\Select\Where\Item\Type;
use Espo\Core\Utils\Language;
use Espo\Modules\Mcp\Tools\Schema\FieldFilter\SchemaProvider;
use Espo\Modules\Mcp\Tools\JsonSchema\ConstSchema;
use Espo\Modules\Mcp\Tools\JsonSchema\GroupKeyword;
use Espo\Modules\Mcp\Tools\JsonSchema\GroupSchema;
use Espo\Modules\Mcp\Tools\JsonSchema\Type\ObjectType;
use Espo\Modules\Mcp\Tools\JsonSchema\Type\StringType;
use Espo\Modules\Mcp\Tools\Schema\FieldFilter\SchemaProvider\Params;
use Espo\ORM\Defs;
use Espo\Tools\OpenApi\Util\EnumOptionsProvider;

/**
 * @noinspection PhpUnused
 */
class EnumSchemaProvider implements SchemaProvider
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

        $value = new StringType();

        $fieldDefs = $this->ormDefs->getEntity($entityType)->getField($field);

        $options = $this->getOptions($fieldDefs);

        if ($options) {
            $value = GroupSchema::createAnyOf(
                schemas: [
                    ...array_map(function (string $it) use ($entityType, $field) {
                        return new ConstSchema(
                            value: $it,
                            // @todo Translate referenced.
                            title: $this->defaultLanguage->translateOption($it, $field, $entityType),
                        );
                    }, $options)
                ],
                description: 'Options.',
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
                            new ConstSchema(
                                value: Type::IN,
                                description: 'Matches one of the listed values.',
                            ),
                            new ConstSchema(
                                value: Type::NOT_IN,
                                description: 'Does not match any of the listed values.',
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
                description: "'$label' field filter. Field type is 'Enum'.",
            ),
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
                description: "'$label' field filter checking if the value is empty or not.",
            ),
        ];
    }

    /**
     * @return ?string[]
     */
    private function getOptions(Defs\FieldDefs $fieldDefs): ?array
    {
        $options = $this->enumOptionsProvider->get($fieldDefs);

        if ($options === null) {
            return null;
        }

        $options = array_filter($options, fn ($it) => $it !== '');

        return array_values($options);
    }
}
