<?php
/**LICENSE**/

namespace Espo\Modules\Mcp\Tools\Feature\Find;

use DateTimeInterface;
use Espo\Core\Field\DateTime;
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
            unset($valueMap->$k);

            return;
        }

        if (!property_exists($valueMap, $k)) {
            return;
        }

        $value = $valueMap->$k;

        // Prevent schema validation failure by the client if the field is required but is null.
        if (
            $value === null &&
            $schema instanceof Type &&
            !$schema instanceof NullType
        ) {
            unset($valueMap->$k);
        }
    }
}
