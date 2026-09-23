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

namespace Espo\Modules\Mcp\Tools\Mcp\Schema\Tool;

use Espo\Modules\Mcp\Tools\Mcp\Schema\General\RootSchema;
use Espo\Modules\Mcp\Tools\Mcp\Schema\General\RootObjectSchema;
use JsonSerializable;
use stdClass;

readonly class Tool implements JsonSerializable
{
    public function __construct(
        public string $name,
        public RootObjectSchema $inputSchema,
        public ?RootSchema $outputSchema = null,
        public ?string $title = null,
        public ?string $description = null,
        public ?ToolAnnotations $annotations = null,
    ) {}

    public function jsonSerialize(): stdClass
    {
        $object = (object) [
            'name' => $this->name,
            'inputSchema' => $this->inputSchema->jsonSerialize(),
        ];

        if ($this->outputSchema) {
            $object->outputSchema = $this->outputSchema->jsonSerialize();
        }

        if ($this->title !== null) {
            $object->title = $this->title;
        }

        if ($this->description !== null) {
            $object->description = $this->description;
        }

        if ($this->annotations) {
            $object->annotations = $this->annotations;
        }

        return $object;
    }
}
