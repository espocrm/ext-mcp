<?php
/**LICENSE**/

namespace Espo\Modules\Mcp\Tools\Feature\Find;

use Espo\Core\Acl;
use Espo\Core\Utils\Language;
use Espo\Core\Utils\Metadata;
use Espo\Modules\Mcp\Schema\FieldFilter\FieldFilterSchemaProviderFactory;
use Espo\Modules\Mcp\Tools\Feature\Data;
use Espo\Modules\Mcp\Tools\Feature\Exceptions\NoUserAccess;
use Espo\Modules\Mcp\Tools\Feature\Exceptions\UnsupportedFeatureValue;
use Espo\Modules\Mcp\Tools\Feature\ToolDefinitionProvider;
use Espo\Modules\Mcp\Tools\JsonSchema\Type\IntegerType;
use Espo\Modules\Mcp\Tools\JsonSchema\Type\ObjectType;
use Espo\Modules\Mcp\Tools\JsonSchema\Type\StringType;
use Espo\Modules\Mcp\Tools\Mcp\Schema\General\ObjectSchema;
use Espo\Modules\Mcp\Tools\Mcp\Schema\Tool\Tool;
use Espo\ORM\Defs;
use stdClass;

/**
 * @implements ToolDefinitionProvider<FindData>
 */
class FindToolDefinitionProvider implements ToolDefinitionProvider
{
    private const int MAX_SIZE_LIMIT = 200;

    private const string DESCRIPTION = "Searches '{scopeName}' records. Supports filtering, sorting, and pagination.";

    private const string MAX_SIZE_DESCRIPTION = 'Maximum number of records to fetch.';

    private const string OFFSET_DESCRIPTION = 'Offset for pagination.';

    private const string SELECT_DESCRIPTION = 'What fields to fetch. ID is always returned. ' .
        'If omitted, all fields from the output schema are fetched.';

    private const string TEXT_FILTER_DESCRIPTION = 'Text filter.';

    private const string BOOL_FILTER_LIST_DESCRIPTION = 'Filters operate as on/off switches. ' .
        'When multiple bool filters are applied, they work inclusively. ' .
        'Omit the parameter entirely to by-pass bool filters.';

    private const string PRIMARY_FILTER_DESCRIPTION = 'Predefined filter.';

    private const string ORDER_DESCRIPTION = 'Sorting direction.';

    private const string ORDER_BY_DESCRIPTION = 'Field to sort by.';

    /**
     * @var array<string, string>
     */
    private array $boolFilterDescriptions = [
        'onlyMy' => "Records assigned to me.",
        'shared' => "Records I'm collaborating in.",
    ];

    public function __construct(
        private Language $defaultLanguage,
        private Defs $ormDefs,
        private Metadata $metadata,
        private FieldFilterSchemaProviderFactory $fieldFilterSchemaProviderFactory,
        private Acl $acl,
    ) {}

    public function get(Data $data): Tool
    {
        if (!$this->acl->tryCheck($data->entityType, Acl\Table::ACTION_READ)) {
            throw new NoUserAccess("No access to '$data->entityType'.");
        }

        return new Tool(
            name: 'Find.' . $data->entityType,
            inputSchema: new ObjectSchema($this->prepareInputSchema($data)),
            description: strtr(self::DESCRIPTION, [
                'scopeName' => $this->defaultLanguage->translateLabel($data->entityType, 'scopeNames'),
            ]),
        );
    }

    private function getBoolFilterListSchema(FindData $data): stdClass
    {
        return (object) [
            'type' => 'array',
            'description' => self::BOOL_FILTER_LIST_DESCRIPTION,
            'items' => (object) [
                'anyOf' => array_map(function (string $filter) use ($data) {
                    $item = (object) [
                        'title' =>
                            $this->defaultLanguage->translateLabel($filter, 'boolFilters', $data->entityType),
                        'const' => $filter,
                    ];

                    $description = $this->boolFilterDescriptions[$filter] ?? null;

                    if ($description !== null) {
                        $item->descripion = $description;
                    }

                    return $item;
                }, $data->boolFilters),
            ],
        ];
    }

    private function getPrimaryFilterSchema(FindData $data): stdClass
    {
        return (object) [
            'description' => self::PRIMARY_FILTER_DESCRIPTION,
            'anyOf' => array_map(function (string $filter) use ($data) {
                return (object) [
                    'title' => $this->defaultLanguage->translateLabel($filter, 'presetFilters', $data->entityType),
                    'const' => $filter,
                ];
            }, $data->primaryFilters),
        ];
    }

    /**
     * @throws UnsupportedFeatureValue
     */
    private function getWhereSchema(FindData $data): stdClass
    {
        $items = [];

        foreach ($data->filterFields as $field) {
            $provider = $this->fieldFilterSchemaProviderFactory->create($data->entityType, $field);

            $fieldSchema =
        }
    }

    private function getOrderBySchema(FindData $data): stdClass
    {
        return (object) [
            'description' => self::ORDER_BY_DESCRIPTION,
            'anyOf' => array_map(function (string $field) use ($data) {
                return (object) [
                    'const' => $field,
                    'title' => $this->defaultLanguage->translateLabel($field, 'fields', $data->entityType),
                ];
            }, $this->getOrderByFields($data)),
        ];
    }

    /**
     * @return string[]
     */
    private function getOrderByFields(FindData $data): array
    {
        $entityDefs = $this->ormDefs->getEntity($data->entityType);

        return array_filter($data->selectFields, function ($field) use ($entityDefs, $data) {
            $fieldDefs = $entityDefs->tryGetField($field);

            if (!$fieldDefs) {
                return false;
            }

            if ($fieldDefs->getParam('orderDisabled')) {
                return false;
            }

            $type = $fieldDefs->getType();

            if (!$this->metadata->get("fields.$type.notSortable")) {
                return false;
            }

            if (!$this->acl->checkField($data->entityType, $field)) {
                return false;
            }

            return true;
        });
    }

    private function getSelectSchema(FindData $data): stdClass
    {
        return (object) [
            'type' => 'array',
            'description' => self::SELECT_DESCRIPTION,
            'items' => (object) [
                'anyOf' => array_map(function (string $field) use ($data) {
                    return (object) [
                        'const' => $field,
                        'title' => $this->defaultLanguage->translateLabel($field, 'fields', $data->entityType),
                    ];
                }, $this->filterFields($data->selectFields, $data->entityType)),
            ],
        ];
    }

    /**
     * @param string[] $fields
     * @return string[]
     */
    private function filterFields(array $fields, string $entityType): array
    {
        $fields = array_filter($fields, function ($field) use ($entityType) {
            return $this->acl->checkField($entityType, $field);
        });

        return array_values($fields);
    }

    /**
     * @throws UnsupportedFeatureValue
     */
    private function prepareInputSchema(FindData $data): ObjectType
    {
        $inputSchemaProperties = [
            'maxSize' => new IntegerType(
                min: 1,
                max: self::MAX_SIZE_LIMIT,
                description: self::MAX_SIZE_DESCRIPTION,
            ),
            'offset' => new IntegerType(
                min: 0,
                description: self::OFFSET_DESCRIPTION,
            ),
            'select' => $this->getSelectSchema($data),
            'order' => (object) [
                'anyOf' => [
                    (object) [
                        'const' => 'asc',
                        'description' => 'Ascending order.',
                    ],
                    (object) [
                        'const' => 'desc',
                        'description' => 'Descending order.',
                    ],
                ],
                'description' => self::ORDER_DESCRIPTION,
            ],
            'orderBy' => $this->getOrderBySchema($data),
        ];

        if ($data->textFilter) {
            $inputSchemaProperties['textFilter'] = new StringType(
                description: self::TEXT_FILTER_DESCRIPTION,
            );
        }

        if ($data->boolFilters) {
            $inputSchemaProperties->boolFilterList = $this->getBoolFilterListSchema($data);
        }

        if ($data->primaryFilters) {
            $inputSchemaProperties->primaryFilter = $this->getPrimaryFilterSchema($data);
        }

        if ($data->filterFields) {
            $inputSchemaProperties->where = $this->getWhereSchema($data);
        }

        return (object) [
            'type' => 'object',
            'properties' => $inputSchemaProperties,
            'additionalProperties' => false,
        ];
    }
}
