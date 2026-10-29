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

namespace Espo\Modules\Mcp\Tools\Feature\Features\RecordStream;

use Espo\Modules\Mcp\Tools\Feature\Data;
use InvalidArgumentException;
use stdClass;

class RecordStreamData implements Data
{
    public const string TYPE = 'RecordStream';

    /**
     * @param string[] $entityTypes
     */
    final public function __construct(
        public array $entityTypes,
    ) {}

    public function composeName(): string
    {
        return $this->getKey();
    }

    public function jsonSerialize(): stdClass
    {
        return (object) [
            'entityTypes' => $this->entityTypes,
        ];
    }

    public static function fromRaw(stdClass $raw): static
    {
        $entityTypes = $raw->entityTypes ?? null;

        if (!is_array($entityTypes)) {
            throw new InvalidArgumentException("No or bad 'entityTypes'.");
        }

        foreach ($entityTypes as $it) {
            if (!is_string($it)) {
                throw new InvalidArgumentException("Bad 'entityTypes' item");
            }
        }

        sort($entityTypes);

        return new static(
            entityTypes: $entityTypes,
        );
    }

    public function getKey(): string
    {
        return self::TYPE;
    }
}
