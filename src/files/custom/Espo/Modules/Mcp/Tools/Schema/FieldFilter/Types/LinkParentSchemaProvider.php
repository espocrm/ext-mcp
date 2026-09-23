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

namespace Espo\Modules\Mcp\Tools\Schema\FieldFilter\Types;

use Espo\Core\Acl;
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
use Espo\ORM\Defs\Params\FieldParam;

/**
 * @noinspection PhpUnused
 */
class LinkParentSchemaProvider implements SchemaProvider
{
    public function __construct(
        private Language $defaultLanguage,
        private Defs $ormDefs,
        private Acl $acl,
    ) {}

    public function get(Params $params): array
    {
        $entityType = $params->entityType;
        $field = $params->field;

        $fieldDefs = $this->ormDefs->getEntity($entityType)->getField($field);

        $foreignEntityTypes = $fieldDefs->getParam('entityList') ?? [];

        if (!is_array($foreignEntityTypes) || !$foreignEntityTypes) {
            return [];
        }

        $foreignEntityTypes = array_filter($foreignEntityTypes, fn ($it) => $this->acl->checkScope($it));
        $foreignEntityTypes = array_values($foreignEntityTypes);

        if (!$foreignEntityTypes) {
            return [];
        }

        $label = $this->defaultLanguage->translateLabel($field, 'fields', $entityType);

        return [
            new ObjectType(
                properties: [
                    'attribute' => new ConstSchema(
                        value: $field . 'Id',
                        description: "Attribute name. Field name plus an `Id` prefix.",
                    ),
                    'type' => new GroupSchema(
                        keyword: GroupKeyword::anyOf,
                        schemas: [
                            new ConstSchema(value: Type::EQUALS),
                            new ConstSchema(value: Type::NOT_EQUALS),
                        ],
                    ),
                    'value' => new StringType(
                        description:
                            "Foreign record ID. " .
                            "Foreign types: " . $this->composeEntityTypesString($foreignEntityTypes) . ". " .
                            "Tools to retrieve IDs: `Find.{entityType}`."
                    ),
                ],
                required: [
                    'attribute',
                    'type',
                    'value',
                ],
                description: "'$label' ID filter. ID of a polymorphic link.",
            ),
            new ObjectType(
                properties: [
                    'attribute' => new ConstSchema(
                        value: $field . 'Type',
                        description: "Attribute name. Field name plus an `Type` prefix.",
                    ),
                    'type' => new GroupSchema(
                        keyword: GroupKeyword::anyOf,
                        schemas: [
                            new ConstSchema(value: Type::EQUALS),
                            new ConstSchema(value: Type::NOT_EQUALS),
                        ],
                    ),
                    'value' => GroupSchema::createAnyOf(
                        schemas: array_map(function (string $it) {
                            return new ConstSchema(
                                value: $it,
                                title: $this->defaultLanguage->translateLabel($it, 'scopeNames'),
                            );
                        }, $foreignEntityTypes),
                        description:
                            "Foreign record Entity Type. " .
                            "Foreign types: " . $this->composeEntityTypesString($foreignEntityTypes)  .  "." ,
                    ),
                ],
                required: [
                    'attribute',
                    'type',
                    'value',
                ],
                description: "'$label' type filter. Entity Type of a polymorphic link.",
            ),
            ...(
                !$fieldDefs->getParam(FieldParam::REQUIRED) ?
                    [
                        new ObjectType(
                            properties: [
                                'attribute' => new ConstSchema(value: $field . 'Id'),
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
                        )
                    ]: []
                ),
        ];
    }

    /**
     * @param string[] $entityTypes
     */
    private function composeEntityTypesString(array $entityTypes): string
    {
        $items = [];

        foreach ($entityTypes as $entityType) {
            $items[] = '`' . $entityType . '`';
        }

        return implode(', ', $items);
    }
}
