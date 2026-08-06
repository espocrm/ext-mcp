<?php
/**LICENSE**/

namespace Espo\Modules\Mcp\Tools\JsonSchema\Type;

use Espo\Modules\Mcp\Tools\JsonSchema\Schema;
use stdClass;

class NumberType implements Schema
{
    public function __construct(
        private int|float|null $min = null,
        private int|float|null $exclusiveMinimum = null,
        private int|float|null $max = null,
        private int|float|null $exclusiveMaximum = null,
        private ?string $title = null,
        private ?string $description = null,
    ) {}

    /**
     * @inheritDoc
     */
    public function jsonSerialize(): stdClass
    {
        $object = (object) [
            'type' => 'number',
        ];

        if ($this->min !== null) {
            $object->min = $this->min;
        }

        if ($this->exclusiveMinimum !== null) {
            $object->exclusiveMinimum = $this->exclusiveMinimum;
        }

        if ($this->max !== null) {
            $object->max = $this->max;
        }

        if ($this->exclusiveMaximum !== null) {
            $object->exclusiveMaximum = $this->exclusiveMaximum;
        }

        if ($this->title !== null) {
            $object->title = $this->title;
        }

        if ($this->description !== null) {
            $object->description = $this->description;
        }

        return $object;
    }
}
