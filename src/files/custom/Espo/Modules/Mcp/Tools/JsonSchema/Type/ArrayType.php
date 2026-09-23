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

use Espo\Modules\Mcp\Tools\JsonSchema\Schema;
use Espo\Modules\Mcp\Tools\Schema\Field\Traits\CommonTrait;
use stdClass;

class ArrayType implements Type
{
    use CommonTrait;

    /**
     * @param ?int<0, max> $minItems
     * @param ?int<0, max> $maxItems
     */
    public function __construct(
        private Schema $items,
        private ?int $minItems = null,
        private ?int $maxItems = null,
        private ?bool $uniqueItems = null,
        private ?string $title = null,
        private ?string $description = null,
    ) {}

    public function jsonSerialize(): stdClass
    {
        $object = (object) [
            'type' => 'array',
            'items' => $this->items->jsonSerialize(),
        ];

        if ($this->minItems !== null) {
            $object->minItems = $this->minItems;
        }

        if ($this->maxItems !== null) {
            $object->maxItems = $this->maxItems;
        }

        if ($this->uniqueItems !== null) {
            $object->uniqueItems = $this->uniqueItems;
        }

        if ($this->title !== null) {
            $object->title = $this->title;
        }

        if ($this->description !== null) {
            $object->description = $this->description;
        }

        return $object;
    }

    public function getItems(): Schema
    {
        return $this->items;
    }

    public function getMinItems(): ?int
    {
        return $this->minItems;
    }

    public function getMaxItems(): ?int
    {
        return $this->maxItems;
    }

    public function getUniqueItems(): ?bool
    {
        return $this->uniqueItems;
    }
}
