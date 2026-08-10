<?php
/**LICENSE**/

namespace Espo\Modules\Mcp\Tools\JsonSchema\Type;

use Espo\Modules\Mcp\Tools\Schema\Field\Traits\CommonTrait;
use stdClass;

class NumberType implements Type
{
    use CommonTrait;

    public function __construct(
        private int|float|null $minimum = null,
        private int|float|null $exclusiveMinimum = null,
        private int|float|null $maximum = null,
        private int|float|null $exclusiveMaximum = null,
        private int|float|null $multipleOf = null,
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

        if ($this->minimum !== null) {
            $object->minimum = $this->minimum;
        }

        if ($this->exclusiveMinimum !== null) {
            $object->exclusiveMinimum = $this->exclusiveMinimum;
        }

        if ($this->maximum !== null) {
            $object->maximum = $this->maximum;
        }

        if ($this->exclusiveMaximum !== null) {
            $object->exclusiveMaximum = $this->exclusiveMaximum;
        }

        if ($this->multipleOf !== null) {
            $object->multipleOf = $this->multipleOf;
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
