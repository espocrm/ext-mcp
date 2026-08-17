<?php
/**LICENSE**/

namespace Espo\Modules\Mcp\Tools\Mcp\Schema\Elicitation;

use Espo\Modules\Mcp\Tools\JsonSchema\Type\Type;
use Espo\Modules\Mcp\Tools\Schema\Field\Traits\CommonTrait;
use stdClass;

class NumberSchema implements Type
{
    use CommonTrait;

    public function __construct(
        private int|float|null $minimum = null,
        private int|float|null $maximum = null,
        private int|float|null $multipleOf = null,
        private ?string $title = null,
        private ?string $description = null,
        private int|float|null $default = null,
    ) {}

    public function jsonSerialize(): stdClass
    {
        $object = (object) [
            'type' => 'number',
        ];

        if ($this->minimum !== null) {
            $object->minimum = $this->minimum;
        }

        if ($this->maximum !== null) {
            $object->maximum = $this->maximum;
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
