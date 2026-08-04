<?php
/**LICENSE**/

namespace Espo\Modules\Mcp\Tools\Mcp\Schema\Discovery;

use JsonSerializable;
use stdClass;

readonly class PromptsCapability implements JsonSerializable
{
    public function __construct(
        public ?bool $listChanged = null,
    ) {}

    public function jsonSerialize(): stdClass
    {
        $object = (object) [];

        if ($this->listChanged !== null) {
            $object->listChanged = $this->listChanged;
        }

        return $object;
    }
}
