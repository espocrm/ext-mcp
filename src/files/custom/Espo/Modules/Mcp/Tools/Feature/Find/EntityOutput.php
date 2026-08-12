<?php
/**LICENSE**/

namespace Espo\Modules\Mcp\Tools\Feature\Find;

use DateTimeInterface;
use Espo\Core\Field\DateTime;
use Espo\ORM\Entity;
use Espo\ORM\Type\AttributeType;
use stdClass;

class EntityOutput
{
    public function prepare(Entity $entity): stdClass
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

        return $valueMap;
    }
}
