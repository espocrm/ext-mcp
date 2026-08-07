<?php
/**LICENSE**/

namespace Espo\Modules\Mcp\Tools\Feature\Find;

use Espo\Core\Acl;
use Espo\Core\Utils\Language;
use Espo\Core\Utils\Metadata;
use Espo\Modules\Mcp\Tools\Schema\Field\FieldSchemaProvider\Action;
use Espo\Modules\Mcp\Tools\Schema\Field\FieldSchemaProvider\Params as FieldSchemaProviderParams;
use Espo\Modules\Mcp\Tools\Schema\FieldFilter\SchemaProvider\Params as FieldFilterSchemaProviderParams;
use Espo\Modules\Mcp\Tools\Schema\FieldFilter\SchemaProviderFactory as FilterFilterSchemaProviderFactory;
use Espo\Modules\Mcp\Tools\Feature\Data;
use Espo\Modules\Mcp\Tools\Feature\Exceptions\NoUserAccess;
use Espo\Modules\Mcp\Tools\Feature\Exceptions\UnsupportedFeatureValue;
use Espo\Modules\Mcp\Tools\Feature\ToolDefinitionProvider;
use Espo\Modules\Mcp\Tools\JsonSchema\ConstSchema;
use Espo\Modules\Mcp\Tools\JsonSchema\GroupKeyword;
use Espo\Modules\Mcp\Tools\JsonSchema\GroupSchema;
use Espo\Modules\Mcp\Tools\JsonSchema\Schema;
use Espo\Modules\Mcp\Tools\JsonSchema\Type\ArrayType;
use Espo\Modules\Mcp\Tools\JsonSchema\Type\IntegerType;
use Espo\Modules\Mcp\Tools\JsonSchema\Type\ObjectType;
use Espo\Modules\Mcp\Tools\JsonSchema\Type\StringType;
use Espo\Modules\Mcp\Tools\Mcp\Schema\General\ArbitrarySchema;
use Espo\Modules\Mcp\Tools\Mcp\Schema\General\ObjectSchema;
use Espo\Modules\Mcp\Tools\Mcp\Schema\Tool\Tool;
use Espo\Modules\Mcp\Tools\Schema\Field\SchemaProviderFactory as FieldSchemaProviderFactory;
use Espo\ORM\Defs;
use Espo\ORM\Name\Attribute;

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

    private const string WHERE_DESCRIPTION = 'Advanced filters. Logical AND is applied for multiple filters.';

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
        private FilterFilterSchemaProviderFactory $fieldFilterSchemaProviderFactory,
        private FieldSchemaProviderFactory $fieldSchemaProviderFactory,
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
            outputSchema: new ArbitrarySchema($this->prepareOutputSchema($data)),
            description: $this->getDescription($data),
        );
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
            'order' => new GroupSchema(
                keyword: GroupKeyword::anyOff,
                schemas:[
                    new ConstSchema(
                        value: 'asc',
                        description: 'Ascending order.',
                    ),
                    new ConstSchema(
                        value: 'desc',
                        description: 'Descending order.',
                    ),
                ],
                description: self::ORDER_DESCRIPTION,
            ),
            'orderBy' => $this->getOrderBySchema($data),
        ];

        if ($data->textFilter) {
            $inputSchemaProperties['textFilter'] = $this->getTextFilterSchema($data);
        }

        if ($data->boolFilters) {
            $inputSchemaProperties['boolFilterList'] = $this->getBoolFilterListSchema($data);
        }

        if ($data->primaryFilters) {
            $inputSchemaProperties['primaryFilter'] = $this->getPrimaryFilterSchema($data);
        }

        if ($data->filterFields) {
            $inputSchemaProperties['where'] = $this->getWhereSchema($data);
        }

        return new ObjectType(
            properties: $inputSchemaProperties,
            additionalProperties: false,
        );
    }

    private function getBoolFilterListSchema(FindData $data): Schema
    {
        return new ArrayType(
            items: GroupSchema::createAnyOf(
                schemas: array_map(function (string $filter) use ($data) {
                    return new ConstSchema(
                        value: $filter,
                        title: $this->defaultLanguage->translateLabel($filter, 'boolFilters', $data->entityType),
                        description: $this->boolFilterDescriptions[$filter] ?? null,
                    );
                }, $data->boolFilters)
            ),
            description: self::BOOL_FILTER_LIST_DESCRIPTION,
        );
    }

    private function getPrimaryFilterSchema(FindData $data): Schema
    {
        return new GroupSchema(
            keyword: GroupKeyword::anyOff,
            schemas: array_map(function (string $filter) use ($data) {
                return new ConstSchema(
                    value: $filter,
                    title: $this->defaultLanguage->translateLabel($filter, 'presetFilters', $data->entityType),
                );
            }, $data->primaryFilters),
            description: self::PRIMARY_FILTER_DESCRIPTION,
        );
    }

    /**
     * @throws UnsupportedFeatureValue
     */
    private function getWhereSchema(FindData $data): Schema
    {
        $schemas = [];

        foreach ($data->filterFields as $field) {
            $provider = $this->fieldFilterSchemaProviderFactory->create($data->entityType, $field);

            $params = new FieldFilterSchemaProviderParams(
                entityType: $data->entityType,
                field: $field,
            );

            $schemas = [...$schemas, ...$provider->get($params)];
        }

        return new ArrayType(
            items: new GroupSchema(
                keyword: GroupKeyword::anyOff,
                schemas: $schemas,
            ),
            description: self::WHERE_DESCRIPTION,
        );
    }

    private function getOrderBySchema(FindData $data): Schema
    {
        return new GroupSchema(
            keyword: GroupKeyword::anyOff,
            schemas: array_map(function (string $field) use ($data) {
                return new ConstSchema(
                    value: $field,
                    title: $this->defaultLanguage->translateLabel($field, 'fields', $data->entityType),
                );
            }, $this->getOrderByFields($data)),
            description: self::ORDER_BY_DESCRIPTION,
        );
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

    private function getSelectSchema(FindData $data): ArrayType
    {
        return new ArrayType(
            items: GroupSchema::createAnyOf(
                schemas: array_map(function (string $field) use ($data) {
                    return new ConstSchema(
                        value: $field,
                        title: $this->defaultLanguage->translateLabel($field, 'fields', $data->entityType)
                    );
                }, $this->filterFields($data->selectFields, $data->entityType))
            ),
            description: self::SELECT_DESCRIPTION,
        );
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

    private function getDescription(FindData $data): string
    {
        return strtr(self::DESCRIPTION, [
            'scopeName' => $this->defaultLanguage->translateLabel($data->entityType, 'scopeNames'),
        ]);
    }

    private function getTextFilterSchema(FindData $data): StringType
    {
        /** @var ?string[] $fields */
        $fields = $this->metadata->get("entityDefs.$data->entityType.collection.textFilterFields");

        $fieldsPart = null;

        if ($fields && is_array($fields)) {
            $translatedFields = array_map(function (string $it) use ($data) {
                return $this->defaultLanguage->translateLabel($it, 'fields', $data->entityType);
            }, $fields);

            $fieldsPart = 'Fields: ' . implode(',', $translatedFields);
        }

        $description = self::TEXT_FILTER_DESCRIPTION;

        if ($fieldsPart) {
            $description .= ' ' . $fieldsPart;
        }

        return new StringType(
            description: $description,
        );
    }

    /**
     * @throws UnsupportedFeatureValue
     */
    private function prepareOutputSchema(FindData $data): Schema
    {
        return new ObjectType(
            properties: [
                'list' => $this->prepareOutputListSchema($data),
                'total' => new IntegerType(
                    description: <<<'EOT'
                        Total number of records in the search result.

                        Special values (if totals disabled):
                         - `-1`: Has more records – pagination can be used to retrieve the next portion.
                         - `-2`: Has no more records – reached the end of the list.
                        EOT
                )
            ],
        );
    }

    /**
     * @throws UnsupportedFeatureValue
     */
    private function prepareOutputListSchema(FindData $data): Schema
    {
        $properties = [];
        $suppress = [];

        $fields = [Attribute::ID, ...$data->selectFields];

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
                action: Action::Find,
            );

            $result = $provider->get($params);

            $properties = array_merge($properties, $result->properties);
            $suppress = array_merge($suppress, $result->suppress);
        }

        return new ArrayType(
            items: new ObjectType(
                properties: $properties,
            ),
            description: "Records.",
        );
    }
}
