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

namespace Espo\Modules\Mcp\Tools\JsonSchema\Type;

use Espo\Modules\Mcp\Tools\JsonSchema\StringFormat;
use Espo\Modules\Mcp\Tools\Schema\Field\Traits\CommonTrait;
use stdClass;

class StringType implements Type
{
    use CommonTrait;

    /**
     * @param ?int<0, max> $minLength
     * @param ?int<0, max> $maxLength
     */
    public function __construct(
        private ?int $minLength = null,
        private ?int $maxLength = null,
        private ?string $pattern = null,
        private ?StringFormat $format = null,
        private ?string $title = null,
        private ?string $description = null,
        private ?string $default = null,
    ) {}

    public function jsonSerialize(): stdClass
    {
        $object = (object) [
            'type' => 'string',
        ];

        if ($this->minLength !== null) {
            $object->minLength = $this->minLength;
        }

        if ($this->maxLength !== null) {
            $object->maxLength = $this->maxLength;
        }

        if ($this->pattern !== null) {
            $object->pattern = $this->pattern;
        }

        if ($this->format) {
            $object->format = $this->format->value;
        }

        if ($this->title !== null) {
            $object->title = $this->title;
        }

        if ($this->description !== null) {
            $object->description = $this->description;
        }

        if ($this->default !== null) {
            $object->default = $this->default;
        }

        return $object;
    }

    public function getMinLength(): ?int
    {
        return $this->minLength;
    }

    public function getMaxLength(): ?int
    {
        return $this->maxLength;
    }

    public function getPattern(): ?string
    {
        return $this->pattern;
    }

    public function getFormat(): ?StringFormat
    {
        return $this->format;
    }

    public function getDefault(): ?string
    {
        return $this->default;
    }
}
