<?php
/**LICENSE**/

namespace Espo\Modules\Mcp\Tools\Feature\Utils;

use Espo\Core\Name\Field;
use Espo\Core\Utils\Config\ApplicationConfig;
use Espo\Modules\Mcp\Tools\Mcp\Schema\Resource\ResourceLink;
use Espo\ORM\Entity;

class ResourceLinkPreparator
{
    public function __construct(
        private ApplicationConfig $applicationConfig,
    ) {}

    public function prepare(Entity $entity, ?string $description = null): ResourceLink
    {
        $entityType = $entity->getEntityType();
        $id = $entity->getId();

        $title = $entity->get(Field::NAME);

        if (!is_string($title)) {
            $title = $id;
        }

        $url = $this->applicationConfig->getSiteUrl() . "#$entityType/view/$id";

        return new ResourceLink(
            name: "$entityType/$id",
            uri: $url,
            title: $title,
            description: $description ?? "Link to the record in the CRM.",
        );
    }
}
