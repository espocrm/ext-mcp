<?php
/**LICENSE**/

namespace Espo\Modules\Mcp\Schema\FieldFilter;

use Espo\Modules\Mcp\Tools\JsonSchema\Type\ObjectItem;

interface FieldFilterSchemaProvider
{
    /**
     * @return ObjectItem[]
     */
    public function get(string $entityType, string $field): array;
}
