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

use Espo\Core\Select\Where\Item\Type;
use Espo\Core\Utils\Language;
use Espo\Modules\Mcp\Tools\JsonSchema\Type\ArrayType;
use Espo\Modules\Mcp\Tools\Schema\FieldFilter\SchemaProvider;
use Espo\Modules\Mcp\Tools\JsonSchema\ConstSchema;
use Espo\Modules\Mcp\Tools\JsonSchema\GroupKeyword;
use Espo\Modules\Mcp\Tools\JsonSchema\GroupSchema;
use Espo\Modules\Mcp\Tools\JsonSchema\Type\ObjectType;
use Espo\Modules\Mcp\Tools\JsonSchema\Type\StringType;
use Espo\Modules\Mcp\Tools\Schema\FieldFilter\SchemaProvider\Params;
use Espo\Modules\Mcp\Tools\Schema\Util\EnumOptionsProvider;
use Espo\Modules\Mcp\Tools\Schema\Util\EnumOptionTranslator;
use Espo\ORM\Defs;

/**
 * @noinspection PhpUnused
 */
class EnumSchemaProvider implements SchemaProvider
{
    public function __construct(
        private EnumOptionsProvider $enumOptionsProvider,
        private Language $defaultLanguage,
        private Defs $ormDefs,
        private EnumOptionTranslator $enumOptionTranslator,
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
            $value = new ArrayType(
                items: GroupSchema::createAnyOf(
                    schemas: [
                        ...array_map(function (string $it) use ($entityType, $field) {
                            return new ConstSchema(
                                value: $it,
                                title: $this->enumOptionTranslator->translate($it, $field, $entityType),
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
