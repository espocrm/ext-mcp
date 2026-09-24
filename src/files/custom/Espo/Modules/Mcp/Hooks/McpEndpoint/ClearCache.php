<?php
/**LICENSE**/

namespace Espo\Modules\Mcp\Hooks\McpEndpoint;

use Espo\Core\Hook\Hook\AfterSave;
use Espo\Modules\Mcp\Entities\Endpoint;
use Espo\Modules\Mcp\Tools\Mcp\Util\Cache\CacheClearer;
use Espo\ORM\Entity;
use Espo\ORM\Repository\Option\SaveOptions;

/**
 * @implements AfterSave<Endpoint>
 */
class ClearCache implements AfterSave
{
    public function __construct(
        private CacheClearer $cacheClearer,
    ) {}

    public function afterSave(Entity $entity, SaveOptions $options): void
    {
        $this->cacheClearer->clear($entity->getId());
    }
}
