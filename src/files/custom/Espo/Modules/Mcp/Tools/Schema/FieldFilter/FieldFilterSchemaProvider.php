<?php
/**LICENSE**/

namespace Espo\Modules\Mcp\Tools\Schema\FieldFilter;

use Espo\Modules\Mcp\Tools\JsonSchema\Type\ObjectType;

interface FieldFilterSchemaProvider
{
    /**
     * @return ObjectType[]
     */
    public function get(string $entityType, string $field): array;
}
