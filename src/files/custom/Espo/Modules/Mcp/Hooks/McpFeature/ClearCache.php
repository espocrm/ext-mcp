<?php
/**LICENSE**/

namespace Espo\Modules\Mcp\Hooks\McpFeature;

use Espo\Core\Hook\Hook\AfterRemove;
use Espo\Core\Hook\Hook\AfterSave;
use Espo\Modules\Mcp\Entities\Feature;
use Espo\Modules\Mcp\Tools\Mcp\Util\Cache\CacheClearer;
use Espo\ORM\Entity;
use Espo\ORM\Repository\Option\RemoveOptions;
use Espo\ORM\Repository\Option\SaveOptions;

/**
 * @implements AfterSave<Feature>
 * @implements AfterRemove<Feature>
 */
class ClearCache implements AfterSave, AfterRemove
{
    public function __construct(
        private CacheClearer $cacheClearer,
    ) {}

    public function afterSave(Entity $entity, SaveOptions $options): void
    {
        $endpointId = $entity->get(Feature::ATTR_ENDPOINT_ID);

        if (!$endpointId) {
            return;
        }

        $this->cacheClearer->clear($endpointId);
    }

    public function afterRemove(Entity $entity, RemoveOptions $options): void
    {
        $endpointId = $entity->get(Feature::ATTR_ENDPOINT_ID);

        if (!$endpointId) {
            return;
        }

        $this->cacheClearer->clear($endpointId);
    }
}
