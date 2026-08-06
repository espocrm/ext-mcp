<?php
/**LICENSE**/

namespace Espo\Modules\Mcp\Tools\Schema\Field;

use Espo\Modules\Mcp\Tools\JsonSchema\Schema;

interface FieldSchemaProvider
{
    /**
     * @return array<string, Schema>
     */
    public function get(string $entityType, string $field): array;
}
