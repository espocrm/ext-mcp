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

namespace Espo\Modules\Mcp\Tools\Feature\Features\Find;

use DateTimeInterface;
use Espo\Core\Field\DateTime;
use Espo\Modules\Mcp\Tools\JsonSchema\EnumSchema;
use Espo\Modules\Mcp\Tools\JsonSchema\GroupKeyword;
use Espo\Modules\Mcp\Tools\JsonSchema\GroupSchema;
use Espo\Modules\Mcp\Tools\JsonSchema\Schema;
use Espo\Modules\Mcp\Tools\JsonSchema\Type\NullType;
use Espo\Modules\Mcp\Tools\JsonSchema\Type\ObjectType;
use Espo\Modules\Mcp\Tools\JsonSchema\Type\Type;
use Espo\ORM\Entity;
use Espo\ORM\Type\AttributeType;
use stdClass;

class EntityOutput
{
    public function prepare(Entity $entity, ObjectType $recordSchema): stdClass
    {
        $valueMap = $entity->getValueMap();

        foreach ($entity->getAttributeList() as $attribute) {
            if (!property_exists($valueMap, $attribute)) {
                continue;
            }

            $value = $valueMap->$attribute;

            if ($entity->getAttributeType($attribute) === AttributeType::DATETIME && is_string($value)) {
                $valueMap->$attribute = DateTime::fromString($value)
                    ->toDateTime()
                    ->format(DateTimeInterface::RFC3339);
            }
        }

        $properties = $recordSchema->getProperties();

        foreach (get_object_vars($valueMap) as $k => $v) {
            $itemSchema = $properties[$k] ?? null;

            $this->prepareItem($itemSchema, $k, $valueMap);
        }

        return $valueMap;
    }

    private function prepareItem(?Schema $schema, string $k, stdClass $valueMap): void
    {
        if (!$schema) {
            $this->unsetKey($valueMap, $k);

            return;
        }

        if (!property_exists($valueMap, $k)) {
            return;
        }

        $value = $valueMap->$k;

        if ($value === null) {
            $this->processNull($schema, $k, $valueMap);
        }
    }

    /**
     * Prevent schema validation failure by the client if the field is required but is null.
     */
    private function processNull(Schema $schema, string $key, stdClass $valueMap): void
    {
        if ($schema instanceof Type && !$schema instanceof NullType) {
            $this->unsetKey($valueMap, $key);

            return;
        }

        if (
            $schema instanceof GroupSchema &&
            $schema->getKeyword() === GroupKeyword::anyOf &&
            array_find_key($schema->getSchemas(), fn ($it) => $it instanceof NullType) === null
        ) {
            $this->unsetKey($valueMap, $key);

            return;
        }

        if (
            $schema instanceof EnumSchema &&
            !in_array(null, $schema->getValues(), true)
        ) {
            $this->unsetKey($valueMap, $key);
        }
    }

    private function unsetKey(stdClass $valueMap, string $key): void
    {
        unset($valueMap->$key);
    }
}
