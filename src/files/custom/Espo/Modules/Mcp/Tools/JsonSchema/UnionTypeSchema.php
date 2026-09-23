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

use Espo\Modules\Mcp\Tools\JsonSchema\Type\Type;
use Espo\Modules\Mcp\Tools\Schema\Field\Traits\CommonTrait;
use LogicException;
use stdClass;
use UnexpectedValueException;

class UnionTypeSchema implements Schema
{
    use CommonTrait;

    /**
     * @param Type[] $schemas
     */
    public function __construct(
        private array $schemas,
        private ?string $title = null,
        private ?string $description = null,
    ) {
        // Validates.
        $this->jsonSerialize();
    }

    public function jsonSerialize(): stdClass
    {
        $types = [];

        $merged = [];

        foreach ($this->schemas as $schema) {
            $item = $schema->jsonSerialize();

            $type = $item->type ?? null;

            if (!is_string($type)) {
                throw new LogicException();
            }

            if (in_array($type, $types)) {
                throw new UnexpectedValueException("Cannot use the same type in union type.");
            }

            $itemAssoc = get_object_vars($item);
            unset($itemAssoc['type']);

            foreach ($itemAssoc as $k => $v) {
                if (array_key_exists($k, $merged)) {
                    throw new UnexpectedValueException("Cannot have same attributes in schemas in union type.");
                }

                $merged[$k] = $v;
            }

            $types[] = $type;
        }

        $object = (object) [
            'type' => $types,
            ...$merged,
        ];

        if ($this->title !== null) {
            $object->title = $this->title;
        }

        if ($this->description !== null) {
            $object->description = $this->description;
        }

        return $object;
    }

    /**
     * @return Type[]
     */
    public function getSchemas(): array
    {
        return $this->schemas;
    }
}
