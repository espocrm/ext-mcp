<?php
/**LICENSE**/

namespace Espo\Modules\Mcp\Hooks\McpEndpoint;

use Espo\Core\Hook\Hook\AfterSave;
use Espo\Core\ORM\Repository\Option\SaveOption;
use Espo\Modules\Mcp\Entities\Endpoint;
use Espo\Modules\Mcp\Entities\Feature;
use Espo\ORM\Entity;
use Espo\ORM\EntityManager;
use Espo\ORM\Name\Attribute;
use Espo\ORM\Repository\Option\SaveOptions;

/**
 * @implements AfterSave<Endpoint>
 */
class Duplicate implements AfterSave
{
    public function __construct(
        private EntityManager $entityManager,
    ) {}

    public function afterSave(Entity $entity, SaveOptions $options): void
    {
        $id = $options->get(SaveOption::DUPLICATE_SOURCE_ID);

        if (!$entity->isNew() || !$id) {
            return;
        }

        $original = $this->entityManager->getRDBRepositoryByClass(Endpoint::class)->getById($id);

        if (!$original) {
            return;
        }

        $this->processCopy($original, $entity);
    }

    private function processCopy(Endpoint $original, Endpoint $endpoint): void
    {
        foreach ($original->getFeatures() as $originalFeature) {
            $this->processCopyFeature($originalFeature, $endpoint);
        }
    }

    private function processCopyFeature(Feature $originalFeature, Endpoint $endpoint): void
    {
        $feature = $this->entityManager->getRDBRepositoryByClass(Feature::class)->getNew();

        $values = $originalFeature->getValueMap();

        unset($values->{Attribute::ID});
        unset($values->{Feature::ATTR_ENDPOINT_ID});

        $feature->setMultiple($values);
        $feature->setEndpoint($endpoint);

        $this->entityManager->saveEntity($feature);
    }
}
