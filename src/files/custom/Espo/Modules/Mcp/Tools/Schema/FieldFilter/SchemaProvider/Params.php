<?php
/**LICENSE**/

namespace Espo\Modules\Mcp\Tools\Schema\FieldFilter\SchemaProvider;

readonly class Params
{
    public function __construct(
        public string $entityType,
        public string $field,
    ) {}
}
