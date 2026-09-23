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

namespace Espo\Modules\Mcp\Tools\JsonSchema;

use Espo\Modules\Mcp\Tools\Schema\Field\Traits\CommonTrait;
use stdClass;

class GroupSchema implements Schema
{
    use CommonTrait;

    /**
     * @param Schema[] $schemas
     * @param scalar|stdClass|stdClass[]|scalar[]|null $default
     */
    public function __construct(
        private GroupKeyword $keyword,
        private array $schemas,
        private ?string $title = null,
        private ?string $description = null,
        private mixed $default = null,
    ) {}

    /**
     * @param Schema[] $schemas
     * @param scalar|stdClass|stdClass[]|scalar[]|null $default
     */
    public static function createAnyOf(
        array $schemas,
        ?string $title = null,
        ?string $description = null,
        mixed $default = null,
    ): self {

        return new self(
            keyword: GroupKeyword::anyOf,
            schemas: $schemas,
            title: $title,
            description: $description,
            default: $default,
        );
    }

    public function jsonSerialize(): stdClass
    {
        $object = (object) [
            $this->keyword->value => array_map(fn ($item) => $item->jsonSerialize(), $this->schemas),
        ];

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

    public function getKeyword(): GroupKeyword
    {
        return $this->keyword;
    }

    /**
     * @return Schema[]
     */
    public function getSchemas(): array
    {
        return $this->schemas;
    }

    /**
     * @return scalar|stdClass|stdClass[]|scalar[]|null
     */
    public function getDefault(): mixed
    {
        return $this->default;
    }
}
