<?php
/**LICENSE**/

namespace Espo\Modules\Mcp\Tools\Feature\Data\Traits;

trait GetKeyTrait
{
    public function getKey(): string
    {
        return $this->entityType . ' . ' . self::TYPE;
    }
}
