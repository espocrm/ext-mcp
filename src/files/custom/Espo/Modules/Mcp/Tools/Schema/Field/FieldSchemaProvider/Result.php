<?php
/**LICENSE**/

namespace Espo\Modules\Mcp\Tools\Schema\Field\FieldSchemaProvider;

use Espo\Modules\Mcp\Tools\JsonSchema\Schema;

readonly class Result
{
    /**
     * @todo When reading 'required', use `array_values(array_unique(...))`.
     * @param array<string, Schema> $properties
     * @param string[] $required
     */
    public function __construct(
        public array $properties = [],
        public array $required = [],
    ) {}
}
