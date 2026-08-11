<?php
/**LICENSE**/

namespace Espo\Modules\Mcp\Tools\JsonSchema\Type;

use Espo\Modules\Mcp\Tools\Schema\Field\Traits\CommonTrait;
use stdClass;

class IntegerType implements Type
{
    use CommonTrait;

    public function __construct(
        private ?int $minimum = null,
        private ?int $exclusiveMinimum = null,
        private ?int $maximum = null,
        private ?int $exclusiveMaximum = null,
        private ?int $multipleOf = null,
        private ?string $title = null,
        private ?string $description = null,
        private ?int $default = null,
    ) {}

    public function jsonSerialize(): stdClass
    {
        $object = (object) [
            'type' => 'integer',
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

        if ($this->default !== null) {
            $object->default = $this->default;
        }

        return $object;
    }
}
