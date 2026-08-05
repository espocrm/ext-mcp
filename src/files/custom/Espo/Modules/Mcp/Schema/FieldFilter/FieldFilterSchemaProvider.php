<?php
/**LICENSE**/

namespace Espo\Modules\Mcp\Schema\FieldFilter;

use stdClass;

interface FieldFilterSchemaProvider
{
    public function get(string $entityType, string $field): stdClass;
}
