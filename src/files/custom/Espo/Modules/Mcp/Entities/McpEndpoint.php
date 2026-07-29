<?php
/**LICENSE**/

namespace Espo\Modules\Mcp\Entities;

use Espo\Core\ORM\Entity;

class McpEndpoint extends Entity
{
    public const string ENTITY_TYPE = 'McpEndpoint';

    public const string FIELD_STATUS = 'status';

    public const string STATUS_ACTIVE = 'Active';

    public function isActive(): bool
    {
        return $this->get(self::FIELD_STATUS) === self::STATUS_ACTIVE;
    }
}
