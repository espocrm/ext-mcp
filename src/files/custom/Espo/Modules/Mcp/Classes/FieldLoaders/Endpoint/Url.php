<?php
/**LICENSE**/

namespace Espo\Modules\Mcp\Classes\FieldLoaders\Endpoint;

use Espo\Core\FieldProcessing\Loader;
use Espo\Core\FieldProcessing\Loader\Params;
use Espo\Core\Utils\Config\ApplicationConfig;
use Espo\Modules\Mcp\Entities\Endpoint;
use Espo\ORM\Entity;
use UnexpectedValueException;

/**
 * @implements Loader<Endpoint>
 */
class Url implements Loader
{
    public function __construct(
        private ApplicationConfig $applicationConfig,
    ) {}

    public function process(Entity $entity, Params $params): void
    {
        try {
            $slug = $entity->getSlug();
        } catch (UnexpectedValueException) {
            return;
        }

        $siteUrl = $this->applicationConfig->getSiteUrl();

        $url = "$siteUrl/api/v1/mcp/$slug";

        $entity->set(Endpoint::FIELD_URL, $url);
    }
}
