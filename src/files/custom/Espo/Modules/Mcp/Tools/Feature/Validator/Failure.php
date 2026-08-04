<?php
/**LICENSE**/

namespace Espo\Modules\Mcp\Tools\Feature\Validator;

readonly class Failure
{
    public function __construct(
        public string $field,
        public ?string $message = null,
    ) {}
}
