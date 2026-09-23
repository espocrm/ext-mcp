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

namespace Espo\Modules\Mcp\Tools\Mcp\Schema\Common;

use JsonSerializable;
use stdClass;

readonly class Annotations implements JsonSerializable
{
    /**
     * @param ?Role[] $audience
     * @param float|int<0, 1>|null $priority A value of 1 means 'most important'.
     */
    public function __construct(
        public ?array $audience = null,
        public float|int|null $priority = null,
        public ?string $lastModified = null,
    ) {}

    public function jsonSerialize(): stdClass
    {
        $object = (object) [];

        if ($this->audience !== null) {
            $object->audience = array_map(fn ($it) => $it->value, $this->audience);
        }

        if ($this->priority !== null) {
            $object->priority = $this->priority;
        }

        if ($this->lastModified !== null) {
            $object->lastModified = $this->lastModified;
        }

        return $object;
    }
}
