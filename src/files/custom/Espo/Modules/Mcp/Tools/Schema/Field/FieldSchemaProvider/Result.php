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

namespace Espo\Modules\Mcp\Tools\Schema\Field\FieldSchemaProvider;

use Espo\Modules\Mcp\Tools\JsonSchema\Schema;

readonly class Result
{
    /**
     * @var string[] Required attributes.
     */
    public array $required;

    /**
     * @param array<string, Schema> $properties
     * @param string[] $required Required attributes.
     * @param string[] $suppress Suppress fields. If a field already defined attributes for a field.
     *     Applied only for next fields.
     */
    public function __construct(
        public array $properties = [],
        array $required = [],
        public array $suppress = [],
    ) {
        $this->required = array_values(array_unique($required));
    }
}
