<?php
/************************************************************************
* This file is part of MCP extension for EspoCRM.
*
* MCP extension for EspoCRM.
* Copyright (C) 2026 EspoCRM, Inc.
* Website: https://www.espocrm.com
*
* This program is free software: you can redistribute it and/or modify
* it under the terms of the GNU Affero General Public License as published by
* the Free Software Foundation, either version 3 of the License, or
* (at your option) any later version.
*
* This program is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
* GNU Affero General Public License for more details.
*
* You should have received a copy of the GNU Affero General Public License
* along with this program. If not, see <https://www.gnu.org/licenses/>.
*
* The interactive user interfaces in modified source and object code versions
* of this program must display Appropriate Legal Notices, as required under
* Section 5 of the GNU Affero General Public License version 3.
*
* In accordance with Section 7(b) of the GNU Affero General Public License version 3,
* these Appropriate Legal Notices must retain the display of the "EspoCRM" word.
************************************************************************/

namespace Espo\Modules\Mcp\Tools\Feature\Features\Find;

use Espo\Core\Acl;
use Espo\Core\Utils\Language;
use Espo\Core\Utils\Metadata;
use Espo\Modules\Mcp\Tools\Feature\Features\Find\FindData\Field;
use Espo\Modules\Mcp\Tools\Mcp\Schema\Tool\ToolAnnotations;
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
use Espo\Modules\Mcp\Tools\Mcp\Schema\General\RootSchema;
use Espo\Modules\Mcp\Tools\Mcp\Schema\General\RootObjectSchema;
use Espo\Modules\Mcp\Tools\Mcp\Schema\Tool\Tool;
use Espo\Modules\Mcp\Tools\Schema\Field\SchemaProviderFactory as FieldSchemaProviderFactory;
use Espo\ORM\Defs;
use Espo\ORM\Name\Attribute;

/**
 * @implements ToolDefinitionProvider<FindData>
 */
class FindToolDefinitionProvider implements ToolDefinitionProvider
{
    public const int MAX_SIZE_LIMIT = 100;

    private const string DESCRIPTION = "Searches '{scopeName}' records. Supports filtering, sorting, and pagination. " .
        "Entity type: `{entityType}`.";

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

    private const string WHERE_DESCRIPTION =
        'Advanced filters. Logical AND is applied when multiple filters are specified.';

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
            name: 'Find_' . $data->entityType,
            inputSchema: new RootObjectSchema($this->prepareInputSchema($data)),
            outputSchema: new RootSchema($this->prepareOutputSchema($data)),
            description: $this->getDescription($data),
            annotations: new ToolAnnotations(
                readOnlyHint: true,
                destructiveHint: false,
                openWorldHint: false,
            ),
        );
    }

    /**
     * @throws UnsupportedFeatureValue
     */
    private function prepareInputSchema(FindData $data): ObjectType
    {
        $properties = [
            'maxSize' => new IntegerType(
                minimum: 1,
                maximum: self::MAX_SIZE_LIMIT,
                description: self::MAX_SIZE_DESCRIPTION,
            ),
            'offset' => new IntegerType(
                minimum: 0,
                description: self::OFFSET_DESCRIPTION,
            ),
            'selectFields' => $this->getSelectFieldsSchema($data),
            'order' => new GroupSchema(
                keyword: GroupKeyword::anyOf,
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
            $properties['textFilter'] = $this->getTextFilterSchema($data);
        }

        if ($data->boolFilters) {
            $properties['boolFilterList'] = $this->getBoolFilterListSchema($data);
        }

        if ($data->primaryFilters) {
            $properties['primaryFilter'] = $this->getPrimaryFilterSchema($data);
        }

        if ($data->filterFields) {
            $properties['where'] = $this->getWhereSchema($data);
        }

        return new ObjectType(
            properties: $properties,
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
        $schemas = array_map(function (string $filter) use ($data) {
            return new ConstSchema(
                value: $filter,
                title: $this->defaultLanguage->translateLabel($filter, 'presetFilters', $data->entityType),
            );
        }, $data->primaryFilters);

        $schemas[] = new ConstSchema(value: null);

        return new GroupSchema(
            keyword: GroupKeyword::anyOf,
            schemas: $schemas,
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
            $provider = $this->fieldFilterSchemaProviderFactory->create($data->entityType, $field->name);

            $params = new FieldFilterSchemaProviderParams(
                entityType: $data->entityType,
                field: $field->name,
            );

            $schemas = [...$schemas, ...$provider->get($params)];
        }

        return new ArrayType(
            items: new GroupSchema(
                keyword: GroupKeyword::anyOf,
                schemas: $schemas,
            ),
            description: self::WHERE_DESCRIPTION,
        );
    }

    private function getOrderBySchema(FindData $data): Schema
    {
        $description = self::ORDER_BY_DESCRIPTION;

        $default = $this->metadata->get("entityDefs.$data->entityType.collection.orderBy");

        if ($default) {
            $defaultLabel = $this->defaultLanguage->translateLabel($default, 'fields', $data->entityType);

            $description .= " If omitted, then sorted by '$defaultLabel'.";
        }

        return new GroupSchema(
            keyword: GroupKeyword::anyOf,
            schemas: array_map(function ($field) use ($data) {
                return new ConstSchema(
                    value: $field,
                    title: $this->defaultLanguage->translateLabel($field, 'fields', $data->entityType),
                );
            }, $this->getOrderByFields($data)),
            description: $description,
        );
    }

    /**
     * @return string[]
     */
    private function getOrderByFields(FindData $data): array
    {
        $entityDefs = $this->ormDefs->getEntity($data->entityType);

        $fields = array_filter($data->selectFields, function ($field) use ($entityDefs, $data) {
            $fieldDefs = $entityDefs->tryGetField($field->name);

            if (!$fieldDefs) {
                return false;
            }

            if ($fieldDefs->getParam('orderDisabled')) {
                return false;
            }

            $type = $fieldDefs->getType();

            if ($this->metadata->get("fields.$type.notSortable")) {
                return false;
            }

            if (!$this->acl->checkField($data->entityType, $field->name)) {
                return false;
            }

            return true;
        });

        $fields = array_values($fields);

        return array_map(fn ($it) => $it->name, $fields);
    }

    private function getSelectFieldsSchema(FindData $data): ArrayType
    {
        return new ArrayType(
            items: GroupSchema::createAnyOf(
                schemas: array_map(function ($field) use ($data) {
                    return new ConstSchema(
                        value: $field->name,
                        title: $this->defaultLanguage->translateLabel($field->name, 'fields', $data->entityType),
                        description: $field->description,
                    );
                }, $this->filterFields($data->selectFields, $data->entityType))
            ),
            description: self::SELECT_DESCRIPTION,
        );
    }

    /**
     * @param Field[] $fields
     * @return Field[]
     */
    private function filterFields(array $fields, string $entityType): array
    {
        $fields = array_filter($fields, function ($field) use ($entityType) {
            return $this->acl->checkField($entityType, $field->name);
        });

        return array_values($fields);
    }

    private function getDescription(FindData $data): string
    {
        return strtr(self::DESCRIPTION, [
            '{scopeName}' => $this->defaultLanguage->translateLabel($data->entityType, 'scopeNames'),
            '{entityType}' => $data->entityType,
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

            $fieldsPart = 'Fields: ' . implode(', ', $translatedFields);
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
                'records' => $this->prepareOutputListSchema($data),
                'total' => new IntegerType(
                    description: <<<'EOT'
                        Total number of records in the search result.

                        Special values (if totals disabled):
                         - `-1`: Has more records – pagination can be used to retrieve the next portion.
                         - `-2`: Has no more records – reached the end of the list.
                        EOT
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
                                    description: "No 'read' access. Or other access error.",
                                ),
                            ],
                            description: 'Error code.',
                        ),
                    ],
                ),
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

        $selectFields = array_map(fn ($it) => $it->name, $data->selectFields);

        $fields = [Attribute::ID, ...$selectFields];

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
