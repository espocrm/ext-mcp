<?php
/**LICENSE**/

namespace Espo\Modules\Mcp\Classes\FieldLoaders\McpEndpoint;

use Espo\Core\FieldProcessing\Loader;
use Espo\Core\FieldProcessing\Loader\Params;
use Espo\Modules\Mcp\Entities\McpEndpoint;
use Espo\ORM\Entity;

/**
 * @implements Loader<McpEndpoint>
 */
class Url implements Loader
{
    public function process(Entity $entity, Params $params): void
    {
        // TODO: Implement process() method.
    }
}
