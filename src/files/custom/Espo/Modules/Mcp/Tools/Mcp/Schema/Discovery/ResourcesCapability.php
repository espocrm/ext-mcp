<?php
/**LICENSE**/

namespace Espo\Modules\Mcp\Tools\Mcp\Schema\Discovery;

use JsonSerializable;
use stdClass;

readonly class ResourcesCapability implements JsonSerializable
{
    public function __construct(
        public ?bool $listChanged = null,
        public ?bool $subscribe = null,
    ) {}

    public function jsonSerialize(): stdClass
    {
        $object = (object) [];

        if ($this->listChanged !== null) {
            $object->listChanged = $this->listChanged;
        }

        if ($this->subscribe !== null) {
            $object->subscribe = $this->subscribe;
        }

        return $object;
    }
}
