<?php
/**LICENSE**/

namespace Espo\Modules\Mcp\Classes\FieldLoaders\Feature\t;

use Espo\Core\FieldProcessing\Loader;
use Espo\Core\FieldProcessing\Loader\Params;
use Espo\Modules\Mcp\Entities\Feature;
use Espo\ORM\Entity;

/**
 * @implements Loader<Feature>
 */
class Text implements Loader
{
    public function process(Entity $entity, Params $params): void
    {
        // TODO: Implement process() method.
    }
}
