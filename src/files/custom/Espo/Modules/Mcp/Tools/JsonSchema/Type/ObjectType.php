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

/**
 * @template TSchema of Schema = Schema
 */
class ObjectType implements Type
{
    use CommonTrait;

    /**
     * @param array<string, TSchema> $properties
     * @param string[] $required,
     */
    public function __construct(
        private array $properties = [],
        private array $required = [],
        private Schema|bool|null $additionalProperties = null,
        private ?string $title = null,
        private ?string $description = null,
    ) {}

    public function jsonSerialize(): stdClass
    {
        $object = (object) [
            'type' => 'object',
        ];

        $properties = array_map(fn ($item) => $item->jsonSerialize(), $this->properties);

        if ($properties) {
            $object->properties = (object) $properties;
        }

        if ($this->required) {
            $object->required = $this->required;
        }

        if ($this->additionalProperties !== null) {
            $object->additionalProperties = is_bool($this->additionalProperties) ?
                $this->additionalProperties :
                $this->additionalProperties->jsonSerialize();
        } else {
            unset($object->additionalProperties);
        }

        if ($this->title !== null) {
            $object->title = $this->title;
        }

        if ($this->description !== null) {
            $object->description = $this->description;
        }

        return $object;
    }

    /**
     * @return array<string, TSchema>
     */
    public function getProperties(): array
    {
        return $this->properties;
    }

    /**
     * @return string[]
     */
    public function getRequired(): array
    {
        return $this->required;
    }

    public function getAdditionalProperties(): Schema|bool|null
    {
        return $this->additionalProperties;
    }
}
