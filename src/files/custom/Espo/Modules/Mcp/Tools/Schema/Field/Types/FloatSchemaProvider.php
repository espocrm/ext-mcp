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
use Espo\Modules\Mcp\Tools\JsonSchema\Type\NumberType;
use Espo\Modules\Mcp\Tools\Schema\Field\FieldSchemaProvider\Params;
use Espo\Modules\Mcp\Tools\Schema\Field\FieldSchemaProvider\Result;
use Espo\Modules\Mcp\Tools\Schema\Field\SchemaProvider;
use Espo\Modules\Mcp\Tools\Schema\Field\Util;
use Espo\ORM\Defs;
use Espo\ORM\Defs\Params\FieldParam;

/**
 * @noinspection PhpUnused
 */
class FloatSchemaProvider implements SchemaProvider
{
    public function __construct(
        private Defs $ormDefs,
        private Language $defaultLanguage,
    ) {}

    public function get(Params $params): Result
    {
        $fieldDefs = $this->ormDefs->getEntity($params->entityType)->getField($params->field);

        $required = [];

        $min = null;
        $max = null;

        if ($fieldDefs->getParam(FieldParam::REQUIRED) && $fieldDefs->getParam(FieldParam::DEFAULT) === null) {
            $required[] = $params->field;
        }

        if ($params->isWriteAction()) {
            $min = $fieldDefs->getParam(FieldParam::MIN);
            $max = $fieldDefs->getParam(FieldParam::MAX);
        }

        $label = $this->defaultLanguage->translateLabel($params->field, 'fields', $params->entityType);

        $property = new NumberType(
            minimum: $min,
            maximum: $max,
            title: $label,
        );

        if (!$fieldDefs->getParam(FieldParam::REQUIRED)) {
            $property = Util::wrapWithNull($property);
        }

        return new Result(
            properties: [
                $params->field => $property,
            ],
            required: $required,
        );
    }
}
