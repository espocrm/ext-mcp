<?php
/**LICENSE**/

namespace Espo\Modules\Mcp\Hooks\McpFeature;

use Espo\Core\Hook\Hook\BeforeSave;
use Espo\Modules\Mcp\Entities\Feature;
use Espo\Modules\Mcp\Tools\Feature\DataFactory;
use Espo\Modules\Mcp\Tools\Feature\Exceptions\BadFeatureData;
use Espo\Modules\Mcp\Tools\Feature\Exceptions\UnsupportedType;
use Espo\ORM\Entity;
use Espo\ORM\Repository\Option\SaveOptions;

/**
 * @implements BeforeSave<Feature>
 */
class SetFields implements BeforeSave
{
    public function __construct(
        private DataFactory $factory,
    ) {}

    public function beforeSave(Entity $entity, SaveOptions $options): void
    {
        $this->setName($entity);
    }

    private function setName(Feature $entity): void
    {
        if (
            !$entity->isNew() &&
            !$entity->isAttributeChanged(Feature::FIELD_DATA) &&
            !$entity->isAttributeChanged(Feature::FIELD_TYPE)
        ) {
            return;
        }

        try {
            $data = $this->factory->createForFeature($entity);
        } catch (BadFeatureData|UnsupportedType) {
            $entity->setName(null);

            return;
        }

        $entity->setName($data->composeName());
    }
}
