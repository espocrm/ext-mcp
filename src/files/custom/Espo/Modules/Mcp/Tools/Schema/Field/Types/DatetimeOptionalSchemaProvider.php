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
use Espo\Modules\Mcp\Tools\JsonSchema\Schema;
use Espo\Modules\Mcp\Tools\JsonSchema\StringFormat;
use Espo\Modules\Mcp\Tools\JsonSchema\Type\StringType;
use Espo\Modules\Mcp\Tools\Schema\Field\FieldSchemaProvider\Params;
use Espo\Modules\Mcp\Tools\Schema\Field\FieldSchemaProvider\Result;
use Espo\Modules\Mcp\Tools\Schema\Field\SchemaProvider;
use Espo\Modules\Mcp\Tools\Schema\Field\Util;

/**
 * @noinspection PhpUnused
 */
class DatetimeOptionalSchemaProvider implements SchemaProvider
{
    public function __construct(
        private Language $defaultLanguage,
        private DatetimeSchemaProvider $datetimeSchemaProvider,
    ) {}

    public function get(Params $params): Result
    {
        $dateField = $this->composeDateFieldName($params);

        $result = $this->datetimeSchemaProvider->get($params);

        return new Result(
            properties: [
                ...$result->properties,
                $dateField => $this->getDateSchema($params),
            ],
            required: $result->required,
            suppress: [$dateField],
        );
    }

    private function getDateSchema(Params $params): Schema
    {
        $dateField = $this->composeDateFieldName($params);

        $label = $this->defaultLanguage->translateLabel($dateField, 'fields', $params->entityType);

        $description = "Is set only when `$params->field` represents all-day (the time part is omitted). " .
            "Should be `null` otherwise.";

        $property = new StringType(
            format: StringFormat::date,
            title: $label,
            description: $description,
        );

        return Util::wrapWithNull($property);
    }

    private function composeDateFieldName(Params $params): string
    {
        return $params->field . 'Date';
    }
}
