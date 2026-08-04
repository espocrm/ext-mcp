<?php
/**LICENSE**/

namespace Espo\Modules\Mcp\Classes\RecordHooks\Feature;

use Espo\Core\Exceptions\Conflict;
use Espo\Core\Exceptions\Error\Body;
use Espo\Core\Record\Hook\SaveHook;
use Espo\Modules\Mcp\Entities\Feature;
use Espo\Modules\Mcp\Tools\Feature\DataFactory;
use Espo\Modules\Mcp\Tools\Feature\Exceptions\BadFeatureData;
use Espo\Modules\Mcp\Tools\Feature\Exceptions\UnsupportedType;
use Espo\ORM\Entity;
use Espo\ORM\EntityManager;

/**
 * @implements SaveHook<Feature>
 */
class BeforeSaveValidation implements SaveHook
{
    public function __construct(
        private EntityManager $entityManager,
        private DataFactory $dataFactory,
    ) {}

    public function process(Entity $entity): void
    {
        $this->validateSameExists($entity);
    }

    /**
     * @throws Conflict
     */
    private function validateSameExists(Feature $entity): void
    {
        if (
            !$entity->isAttributeChanged(Feature::FIELD_DATA) &&
            !$entity->isAttributeChanged(Feature::FIELD_TYPE)
        ) {
            return;
        }

        $endpoint = $entity->getEndpoint();

        $features = $this->entityManager
            ->getRDBRepositoryByClass(Feature::class)
            ->sth()
            ->where([
                Feature::LINK_ENDPOINT . 'Id' => $endpoint->getId()
            ])
            ->find();

        foreach ($features as $feature) {
            if (!$entity->isNew() && $feature->getId() === $entity->getId()) {
                continue;
            }

            if ($entity->getType() !== $feature->getType()) {
                continue;
            }

            try {
                $data = $this->dataFactory->createForFeature($entity);
                $dataOther = $this->dataFactory->createForFeature($feature);
            } catch (BadFeatureData|UnsupportedType) {
                continue;
            }

            if ($data->getKey() === $dataOther->getKey()) {
                throw Conflict::createWithBody(
                    'featureAlreadyExists',
                    Body::create()->withMessageTranslation('featureAlreadyExists', Feature::ENTITY_TYPE)
                );
            }
        }
    }
}
