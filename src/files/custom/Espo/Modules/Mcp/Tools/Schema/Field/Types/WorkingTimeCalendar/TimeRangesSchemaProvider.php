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

namespace Espo\Modules\Mcp\Tools\Schema\Field\Types\WorkingTimeCalendar;

use Espo\Core\Utils\Language;
use Espo\Modules\Mcp\Tools\JsonSchema\Type\ArrayType;
use Espo\Modules\Mcp\Tools\JsonSchema\Type\StringType;
use Espo\Modules\Mcp\Tools\Schema\Field\FieldSchemaProvider\Params;
use Espo\Modules\Mcp\Tools\Schema\Field\FieldSchemaProvider\Result;
use Espo\Modules\Mcp\Tools\Schema\Field\SchemaProvider;
use Espo\Modules\Mcp\Tools\Schema\Field\Util;
use Espo\ORM\Defs;
use Espo\ORM\Defs\Params\FieldParam;

/**
 * @noinspection PhpUnused
 */
class TimeRangesSchemaProvider implements SchemaProvider
{
    /**
     * `HH:mm`.
     */
    private const string PATTERN = '^([01][0-9]|2[0-3]):[0-5][0-9]$';

    public function __construct(
        private Defs $ormDefs,
        private Language $defaultLanguage,
    ) {}

    public function get(Params $params): Result
    {
        $fieldDefs = $this->ormDefs->getEntity($params->entityType)->getField($params->field);

        $isRequired = (bool) $fieldDefs->getParam(FieldParam::REQUIRED);

        $required = [];

        if ($isRequired && $fieldDefs->getParam(FieldParam::DEFAULT) === null) {
            $required[] = $params->field;
        }

        $label = $this->defaultLanguage->translateLabel($params->field, 'fields', $params->entityType);

        $time = new StringType(
            pattern: self::PATTERN,
        );

        $range = new ArrayType(
            items: $time,
            minItems: 2,
            maxItems: 2,
        );

        $property = new ArrayType(
            items: $range,
            minItems: 1,
            title: $label,
            description: $this->getDescription($params),
        );

        if (!$isRequired) {
            $property = Util::wrapWithNull($property);
        }

        return new Result(
            properties: [
                $params->field => $property,
            ],
            required: $required,
        );
    }

    private function getDescription(Params $params): string
    {
        $description = "Time ranges as `[start, end]` pairs in 24-hour `HH:mm` format.";

        if (!$params->isWriteAction()) {
            return $description;
        }

        return $description . " " .
            "Ranges must be in ascending order and must not overlap. " .
            "The end must be after the start. `00:00` as an end means midnight, and only the last range " .
            "can end at midnight. Example: `[[\"09:00\", \"13:00\"], [\"14:00\", \"18:00\"]]`.";
    }
}
