<?php
/**LICENSE**/

namespace Espo\Modules\Mcp\Tools\Feature\Find\FindData;

readonly class Field
{
    public function __construct(
        public string $name,
        public ?string $description = null,
    ) {}
}
