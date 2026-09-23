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

namespace Espo\Modules\Mcp\Tools\Schema\Field\Types;

use Espo\Core\Utils\Language;
use Espo\Modules\Mcp\Tools\JsonSchema\ConstSchema;
use Espo\Modules\Mcp\Tools\JsonSchema\GroupSchema;
use Espo\Modules\Mcp\Tools\Schema\Field\FieldSchemaProvider\Params;
use Espo\Modules\Mcp\Tools\Schema\Field\FieldSchemaProvider\Result;
use Espo\Modules\Mcp\Tools\Schema\Field\SchemaProvider;
use Espo\Modules\Mcp\Tools\Schema\Util\EnumOptionsProvider;
use Espo\Modules\Mcp\Tools\Schema\Util\EnumOptionTranslator;
use Espo\ORM\Defs;
use Espo\ORM\Defs\Params\FieldParam;

/**
 * @noinspection PhpUnused
 */
class EnumSchemaProvider implements SchemaProvider
{
    public function __construct(
        private Defs $ormDefs,
        private Language $defaultLanguage,
        private EnumOptionsProvider $enumOptionsProvider,
        private EnumOptionTranslator $enumOptionTranslator,
    ) {}

    public function get(Params $params): Result
    {
        $fieldDefs = $this->ormDefs->getEntity($params->entityType)->getField($params->field);

        $default = $fieldDefs->getParam(FieldParam::DEFAULT);

        $required = [];

        if ($fieldDefs->getParam(FieldParam::REQUIRED) && $default === null) {
            $required[] = $params->field;
        }

        $label = $this->defaultLanguage->translateLabel($params->field, 'fields', $params->entityType);

        $options = $this->enumOptionsProvider->get($fieldDefs);
        $nonEmptyOptions = $this->getNonEmptyOptions($fieldDefs);

        if (!$options || !$nonEmptyOptions) {
            return new Result();
        }

        $schemas = [
            ...array_map(function (string $it) use ($params) {
                return new ConstSchema(
                    value: $it,
                    title: $this->enumOptionTranslator->translate($it, $params->field, $params->entityType),
                );
            }, $nonEmptyOptions)
        ];

        if (in_array('', $options, true) && !$fieldDefs->getParam(FieldParam::REQUIRED)) {
            $schemas[] = new ConstSchema(value: null);
        }

        $property = GroupSchema::createAnyOf(
            schemas: $schemas,
            title: $label,
            default: $params->isWriteAction() ? $default : null,
        );

        return new Result(
            properties: [
                $params->field => $property,
            ],
            required: $required,
        );
    }

    /**
     * @return ?string[]
     */
    private function getNonEmptyOptions(Defs\FieldDefs $fieldDefs): ?array
    {
        $options = $this->enumOptionsProvider->get($fieldDefs);

        if ($options === null) {
            return null;
        }

        $options = array_filter($options, fn ($it) => $it !== '');

        return array_values($options);
    }
}
