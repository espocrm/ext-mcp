<?php
/**LICENSE**/

namespace Espo\Modules\Mcp\Schema\FieldFilter\Types;

use Espo\Modules\Mcp\Schema\FieldFilter\FieldFilterSchemaProvider;
use Espo\Modules\Mcp\Tools\JsonSchema\Type\ObjectType;

class VarcharFilterSchemaProvider implements FieldFilterSchemaProvider
{
    /**
     * @return ObjectType[]
     */
    public function get(string $entityType, string $field): array
    {
        // TODO: Implement get() method.
    }
}
