<?php
/**LICENSE**/

namespace Espo\Modules\Mcp\Tools\Schema\FieldFilter\Types;

use Espo\Core\Select\Where\Item\Type;
use Espo\Core\Utils\Language;
use Espo\Modules\Mcp\Tools\Schema\FieldFilter\FieldFilterSchemaProvider;
use Espo\Modules\Mcp\Tools\JsonSchema\ConstSchema;
use Espo\Modules\Mcp\Tools\JsonSchema\GroupKeyword;
use Espo\Modules\Mcp\Tools\JsonSchema\GroupSchema;
use Espo\Modules\Mcp\Tools\JsonSchema\StringFormat;
use Espo\Modules\Mcp\Tools\JsonSchema\Type\ObjectType;
use Espo\Modules\Mcp\Tools\JsonSchema\Type\StringType;
use Espo\Modules\Mcp\Tools\Schema\FieldFilter\FieldFilterSchemaProvider\Params;

/**
 * @noinspection PhpUnused
 */
class EmailFilterSchemaProvider implements FieldFilterSchemaProvider
{
    public function __construct(
        private Language $defaultLanguage,
    ) {}

    public function get(Params $params): array
    {
        $entityType = $params->entityType;
        $field = $params->field;

        $label = $this->defaultLanguage->translateLabel($field, 'fields', $entityType);

        return [
            new ObjectType(
                properties: [
                    'attribute' => new ConstSchema(value: $field),
                    'type' => new GroupSchema(
                        keyword: GroupKeyword::anyOff,
                        schemas: [
                            new ConstSchema(value: Type::EQUALS),
                        ],
                    ),
                    'value' => new StringType(
                        format: StringFormat::email,
                        description: "Query string.",
                    ),
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
}
