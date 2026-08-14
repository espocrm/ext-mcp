<?php
/**LICENSE**/

namespace Espo\Modules\Mcp\Tools\Mcp\Schema\Common;

use JsonSerializable;
use stdClass;

readonly class Annotations implements JsonSerializable
{
    /**
     * @param ?Role[] $audience
     * @param float|int<0, 1>|null $priority A value of 1 means 'most important'.
     */
    public function __construct(
        public ?array $audience = null,
        public float|int|null $priority = null,
        public ?string $lastModified = null,
    ) {}

    public function jsonSerialize(): stdClass
    {
        $object = (object) [];

        if ($this->audience !== null) {
            $object->audience = array_map(fn ($it) => $it->value, $this->audience);
        }

        if ($this->priority !== null) {
            $object->priority = $this->priority;
        }

        if ($this->lastModified !== null) {
            $object->lastModified = $this->lastModified;
        }

        return $object;
    }
}
