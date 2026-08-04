<?php
/**LICENSE**/

namespace Espo\Modules\Mcp\Entities;

use Espo\Core\ORM\Entity;
use stdClass;
use UnexpectedValueException;

class Feature extends Entity
{
    public const string ENTITY_TYPE = 'McpFeature';

    public const string FIELD_STATUS = 'status';
    public const string FIELD_TYPE = 'type';
    public const string FIELD_DATA = 'data';

    public const string STATUS_ACTIVE = 'Active';

    public function isActive(): bool
    {
        return $this->get(self::FIELD_STATUS) === self::STATUS_ACTIVE;
    }

    public function setType(string $type): self
    {
        return $this->set(self::FIELD_TYPE, $this);
    }

    public function getType(): string
    {
        return $this->get(self::FIELD_TYPE) ?? throw new UnexpectedValueException("No type.");
    }

    public function getRawData(): stdClass
    {
        return $this->get(self::FIELD_DATA) ?? throw new UnexpectedValueException("No data.");
    }
}
