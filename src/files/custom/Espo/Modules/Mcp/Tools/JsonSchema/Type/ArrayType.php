<?php
/**LICENSE**/

namespace Espo\Modules\Mcp\Tools\JsonSchema\Type;

use Espo\Modules\Mcp\Tools\JsonSchema\Schema;
use Espo\Modules\Mcp\Tools\Schema\Field\Traits\CommonTrait;
use stdClass;

class ArrayType implements Type
{
    use CommonTrait;

    public function __construct(
        private Schema $items,
        private ?int $minItems = null,
        private ?int $maxItems = null,
        private ?bool $uniqueItems = null,
        private ?string $title = null,
        private ?string $description = null,
    ) {}

    public function jsonSerialize(): stdClass
    {
        $object = (object) [
            'type' => 'array',
            'items' => $this->items->jsonSerialize(),
        ];

        if ($this->minItems !== null) {
            $object->minItems = $this->minItems;
        }

        if ($this->minItems !== null) {
            $object->minItems = $this->minItems;
        }

        if ($this->maxItems !== null) {
            $object->maxItems = $this->maxItems;
        }

        if ($this->uniqueItems !== null) {
            $object->uniqueItems = $this->uniqueItems;
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
