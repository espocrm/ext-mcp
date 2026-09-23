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

namespace Espo\Modules\Mcp\Tools\Mcp\Schema\Discovery;

use Espo\Modules\Mcp\Tools\Mcp\Schema\Value\CacheScope;
use Espo\Modules\Mcp\Tools\Mcp\Schema\Value\ResultType;
use JsonSerializable;
use stdClass;

readonly class DiscoverResult implements JsonSerializable
{
    /**
     * @param string[] $supportedVersions
     */
    public function __construct(
        public array $supportedVersions,
        public ServerCapabilities $capabilities,
        public ResultType $resultType = ResultType::Complete,
        public ?string $instructions = null,
        public int $ttlMs = 0,
        public CacheScope $cacheScope = CacheScope::Private,
    ) {}

    public function jsonSerialize(): stdClass
    {
        $object = (object) [
            'resultType' => $this->resultType->value,
            'supportedVersions' => $this->supportedVersions,
            'capabilities' => $this->capabilities->jsonSerialize(),
            'ttlMs' => $this->ttlMs,
            'cacheScope' => $this->cacheScope->value,
        ];

        if ($this->instructions) {
            $object->instructions = $this->instructions;
        }

        return $object;
    }
}
