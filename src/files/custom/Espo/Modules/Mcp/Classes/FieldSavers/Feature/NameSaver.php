<?php
/**LICENSE**/

namespace Espo\Modules\Mcp\Classes\FieldSavers\Feature;

use Espo\Core\FieldProcessing\Saver;
use Espo\Core\FieldProcessing\Saver\Params;
use Espo\Modules\Mcp\Entities\Feature;
use Espo\Modules\Mcp\Tools\Feature\DataFactory;
use Espo\Modules\Mcp\Tools\Feature\Exceptions\BadFeatureData;
use Espo\Modules\Mcp\Tools\Feature\Exceptions\UnsupportedType;
use Espo\ORM\Entity;

/**
 * @implements Saver<Feature>
 */
class NameSaver implements Saver
{
    public function __construct(
        private DataFactory $factory,
    ) {}

    public function process(Entity $entity, Params $params): void
    {
        try {
            $data = $this->factory->createForFeature($entity);
        } catch (BadFeatureData|UnsupportedType) {
            $entity->setName(null);

            return;
        }

        $entity->setName($data->composeName());
    }
}
