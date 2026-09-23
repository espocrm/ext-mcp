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

namespace Espo\Modules\Mcp\Tools\Mcp\Schema\Resource;

use Espo\Modules\Mcp\Tools\Mcp\Schema\Common\Annotations;
use JsonSerializable;
use stdClass;

readonly class ResourceLink implements JsonSerializable
{
    /**
     * @param string $name Intended for programmatic or logical use.
     * @param ?int $size Size in bytes.
     * @param ?Annotations $annotations
     */
    public function __construct(
        public string $name,
        public string $uri,
        public ?string $title = null,
        public ?string $description = null,
        public ?string $mimeType = null,
        public ?int $size = null,
        public ?Annotations $annotations = null,
    ) {}

    public function jsonSerialize(): stdClass
    {
        $object = (object) [
            'type' => 'resource_link',
            'name' => $this->name,
            'uri' => $this->uri,
        ];

        if ($this->title !== null) {
            $object->title = $this->title;
        }

        if ($this->description !== null) {
            $object->description = $this->description;
        }

        if ($this->mimeType !== null) {
            $object->mimeType = $this->mimeType;
        }

        if ($this->size !== null) {
            $object->size = $this->size;
        }

        if ($this->annotations !== null) {
            $object->annotations = $this->annotations->jsonSerialize();
        }

        return $object;
    }
}
