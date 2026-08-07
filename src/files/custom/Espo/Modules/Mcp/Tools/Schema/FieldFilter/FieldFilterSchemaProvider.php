<?php
/**LICENSE**/

namespace Espo\Modules\Mcp\Tools\Schema\FieldFilter;

use Espo\Modules\Mcp\Tools\JsonSchema\Type\ObjectType;
use Espo\Modules\Mcp\Tools\Schema\FieldFilter\FieldFilterSchemaProvider\Params;

interface FieldFilterSchemaProvider
{
    /**
     * @return ObjectType[]
     */
    public function get(Params $params): array;
}
