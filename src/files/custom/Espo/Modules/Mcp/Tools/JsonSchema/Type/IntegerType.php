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

use Espo\Modules\Mcp\Tools\Schema\Field\Traits\CommonTrait;
use stdClass;

class IntegerType implements Type
{
    use CommonTrait;

    public function __construct(
        private ?int $minimum = null,
        private ?int $exclusiveMinimum = null,
        private ?int $maximum = null,
        private ?int $exclusiveMaximum = null,
        private ?int $multipleOf = null,
        private ?string $title = null,
        private ?string $description = null,
        private ?int $default = null,
    ) {}

    public function jsonSerialize(): stdClass
    {
        $object = (object) [
            'type' => 'integer',
        ];

        if ($this->minimum !== null) {
            $object->minimum = $this->minimum;
        }

        if ($this->exclusiveMinimum !== null) {
            $object->exclusiveMinimum = $this->exclusiveMinimum;
        }

        if ($this->maximum !== null) {
            $object->maximum = $this->maximum;
        }

        if ($this->exclusiveMaximum !== null) {
            $object->exclusiveMaximum = $this->exclusiveMaximum;
        }

        if ($this->multipleOf !== null) {
            $object->multipleOf = $this->multipleOf;
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

    public function getMinimum(): ?int
    {
        return $this->minimum;
    }

    public function getExclusiveMinimum(): ?int
    {
        return $this->exclusiveMinimum;
    }

    public function getMaximum(): ?int
    {
        return $this->maximum;
    }

    public function getExclusiveMaximum(): ?int
    {
        return $this->exclusiveMaximum;
    }

    public function getMultipleOf(): ?int
    {
        return $this->multipleOf;
    }

    public function getDefault(): ?int
    {
        return $this->default;
    }
}
