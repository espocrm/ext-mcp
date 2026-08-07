<?php
/**LICENSE**/

namespace Espo\Modules\Mcp\Tools\Schema\FieldFilter;

use Espo\Modules\Mcp\Tools\JsonSchema\Type\ObjectType;
use Espo\Modules\Mcp\Tools\Schema\FieldFilter\SchemaProvider\Params;

interface SchemaProvider
{
    /**
     * @return ObjectType[]
     */
    public function get(Params $params): array;
}
