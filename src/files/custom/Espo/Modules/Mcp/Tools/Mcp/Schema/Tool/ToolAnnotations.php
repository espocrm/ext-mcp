<?php
/**LICENSE**/

namespace Espo\Modules\Mcp\Tools\Mcp\Schema\Tool;

use JsonSerializable;
use stdClass;

readonly class ToolAnnotations implements JsonSerializable
{
    public function __construct(
        public ?string $title = null,
        public bool $readOnlyHint = false,
        public bool $destructiveHint = false,
        public bool $idempotentHint = false,
        public bool $openWorldHint = false,
    ) {}

    public function jsonSerialize(): stdClass
    {
        $object = (object) get_object_vars($this);

        if ($this->title === null) {
            unset($object->title);
        }

        return $object;
    }
}
