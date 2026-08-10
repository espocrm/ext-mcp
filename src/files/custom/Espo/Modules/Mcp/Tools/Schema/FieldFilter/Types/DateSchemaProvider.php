<?php
/**LICENSE**/

namespace Espo\Modules\Mcp\Tools\Schema\FieldFilter\Types;

use Espo\Core\Select\Where\Item\Type;
use Espo\Core\Utils\Language;
use Espo\Modules\Mcp\Tools\Schema\FieldFilter\SchemaProvider;
use Espo\Modules\Mcp\Tools\JsonSchema\ConstSchema;
use Espo\Modules\Mcp\Tools\JsonSchema\GroupKeyword;
use Espo\Modules\Mcp\Tools\JsonSchema\GroupSchema;
use Espo\Modules\Mcp\Tools\JsonSchema\StringFormat;
use Espo\Modules\Mcp\Tools\JsonSchema\Type\ObjectType;
use Espo\Modules\Mcp\Tools\JsonSchema\Type\StringType;
use Espo\Modules\Mcp\Tools\Schema\FieldFilter\SchemaProvider\Params;
use Espo\ORM\Defs;
use Espo\ORM\Defs\Params\FieldParam;

/**
 * @noinspection PhpUnused
 */
class DateSchemaProvider implements SchemaProvider
{
    protected bool $isDateTime = false;

    public function __construct(
        private Language $defaultLanguage,
        private Defs $ormDefs,
    ) {}

    public function get(Params $params): array
    {
        $entityType = $params->entityType;
        $field = $params->field;

        $fieldDefs = $this->ormDefs->getEntity($entityType)->getField($field);
        $label = $this->defaultLanguage->translateLabel($field, 'fields', $entityType);

        return [
            new ObjectType(
                properties: [
                    'attribute' => new ConstSchema(value: $field),
                    'type' => new GroupSchema(
                        keyword: GroupKeyword::anyOf,
                        schemas: [
                            new ConstSchema(
                                value: Type::ON,
                                description: 'Date falls on the specified in the query date.',
                            ),
                            new ConstSchema(
                                value: Type::NOT_ON,
                                description: 'Date does not fall on the specified in the query date.',
                            ),
                            new ConstSchema(
                                value: Type::AFTER,
                                description: 'Date is after the specified in the query date.',
                            ),
                            new ConstSchema(
                                value: Type::BEFORE,
                                description: 'Date is before the specified in the query date.',
                            ),
                        ],
                    ),
                    'value' => new StringType(
                        format: StringFormat::date,
                        description: 'Query date.',
                    ),
                    ...$this->getDateTimePart(),
                ],
                required: [
                    'attribute',
                    'type',
                    'value',
                    ...($this->isDateTime ? ['dateTime']: []),
                ],
                description: "'$label' field filter. Field type is 'Date'.",
            ),
            new ObjectType(
                properties: [
                    'attribute' => new ConstSchema(value: $field),
                    'type' => new GroupSchema(
                        keyword: GroupKeyword::anyOf,
                        schemas: [
                            new ConstSchema(
                                value: Type::TODAY,
                                description: "Value is today's date.",
                            ),
                            new ConstSchema(
                                value: Type::CURRENT_MONTH,
                                description: "Value falls on the current month.",
                            ),
                            new ConstSchema(
                                value: Type::LAST_MONTH,
                                description: "Value falls on the previous month.",
                            ),
                            new ConstSchema(
                                value: Type::CURRENT_YEAR,
                                description: "Value falls on the current year.",
                            ),
                            new ConstSchema(
                                value: Type::LAST_YEAR,
                                description: "Value falls on the previous year.",
                            ),
                            new ConstSchema(
                                value: Type::CURRENT_QUARTER,
                                description: "Value falls on the current quarter.",
                            ),
                            new ConstSchema(
                                value: Type::LAST_QUARTER,
                                description: "Value falls on the previous quarter.",
                            ),
                            new ConstSchema(
                                value: Type::CURRENT_FISCAL_QUARTER,
                                description: "Value falls on the current fiscal quarter.",
                            ),
                            new ConstSchema(
                                value: Type::LAST_FISCAL_QUARTER,
                                description: "Value falls on the last fiscal quarter.",
                            ),
                            ...(
                                !$fieldDefs->getParam(FieldParam::REQUIRED) ?
                                    [
                                        new ConstSchema(
                                            value: Type::IS_NOT_NULL,
                                            description: 'Value is set.',
                                        ),
                                        new ConstSchema(
                                            value: Type::IS_NULL,
                                            description: 'Value is not set.',
                                        ),
                                    ]: []
                            ),
                        ],
                    ),
                    ...$this->getDateTimePart(),
                ],
                required: [
                    'attribute',
                    'type',
                    ...($this->isDateTime ? ['dateTime']: []),
                ],
                description: "'$label' field filter.",
            ),
        ];
    }

    /**
     * @return array<string, ConstSchema>
     */
    private function getDateTimePart(): array
    {
        if (!$this->isDateTime) {
            return [];
        }

        return [
            'dateTime' => new ConstSchema(value: true),
        ];
    }
}
