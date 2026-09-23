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
use Espo\Modules\Mcp\Tools\JsonSchema\Type\ArrayType;
use Espo\Modules\Mcp\Tools\JsonSchema\Type\ObjectType;
use Espo\Modules\Mcp\Tools\JsonSchema\Type\StringType;
use Espo\Modules\Mcp\Tools\Schema\FieldFilter\SchemaProvider\Params;
use Espo\ORM\Defs;
use Espo\ORM\Defs\Params\FieldParam;

/**
 * @noinspection PhpUnused
 */
class LinkMultipleSchemaProvider implements SchemaProvider
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
        $linkDefs = $this->ormDefs->getEntity($entityType)->tryGetRelation($field);

        if (!$linkDefs) {
            return [];
        }

        $foreignEntityType = $linkDefs->tryGetForeignEntityType();

        if (!$foreignEntityType) {
            return [];
        }

        if (!$this->acl->checkScope($foreignEntityType)) {
            return [];
        }

        $label = $this->defaultLanguage->translateLabel($field, 'fields', $entityType);

        $foreignScopeLabel = $this->defaultLanguage->translateLabel($foreignEntityType, 'scopeNames');

        return [
            new ObjectType(
                properties: [
                    'type' => new GroupSchema(
                        keyword: GroupKeyword::anyOf,
                        schemas: [
                            new ConstSchema(
                                value: Type::IS_LINKED_WITH,
                                description: 'Is linked with at least one of the provided records.',
                            ),
                            new ConstSchema(
                                value: Type::IS_LINKED_WITH_ALL,
                                description: 'Is linked with all provided records.',
                            ),
                            new ConstSchema(
                                value: Type::IS_NOT_LINKED_WITH,
                                description: 'Is linked with all provided records.',
                            ),
                        ],
                    ),
                    'attribute' => new ConstSchema(
                        value: $field,
                    ),
                    'value' => new ArrayType(
                        items: new StringType(
                            description: 'Record ID.',
                        ),
                        description:
                            "'$foreignScopeLabel' record IDs. Foreign type: `$foreignEntityType`. " .
                            "Tool to retrieve IDs: `Find.$foreignEntityType`."
                    ),
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
                                'type' => new GroupSchema(
                                    keyword: GroupKeyword::anyOf,
                                    schemas: [
                                        new ConstSchema(
                                            value: Type::IS_LINKED_WITH_ANY,
                                            description: 'Is not empty.',
                                        ),
                                        new ConstSchema(
                                            value: Type::IS_LINKED_WITH_NONE,
                                            description: 'Is empty.',
                                        ),
                                    ],
                                ),
                                'attribute' => new ConstSchema(
                                    value: $field,
                                ),
                            ],
                            required: [
                                'type',
                                'attribute',
                            ],
                            description: "'$label' field filter checking if the value is empty or not.",
                        )
                    ]: []
            ),
        ];
    }
}
