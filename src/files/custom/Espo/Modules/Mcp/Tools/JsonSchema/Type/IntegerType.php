<?php
/**LICENSE**/

namespace Espo\Modules\Mcp\Tools\JsonSchema\Type;

use Espo\Modules\Mcp\Tools\JsonSchema\Schema;
use stdClass;

class IntegerType extends Schema
{
    public function __construct(
        private ?int $min = null,
        private ?int $max = null,
        private ?string $description = null,
    ) {}

    public function withMin(?int $min): self
    {
        $object = clone $this;
        $object->min = $min;

        return $object;
    }

    public function withMax(?int $max): self
    {
        $object = clone $this;
        $object->max = $max;

        return $object;
    }

    public function withDescription(?string $description): self
    {
        $object = clone $this;
        $object->description = $description;

        return $object;
    }

    public function jsonSerialize(): stdClass
    {
        $object = (object) [
            'type' => 'integer',
        ];

        if ($this->min !== null) {
            $object->min = $this->min;
        }

        if ($this->max !== null) {
            $object->max = $this->max;
        }

        if ($this->description !== null) {
            $object->description = $this->description;
        }

        return $object;
    }
}
