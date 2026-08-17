<?php
/**LICENSE**/

namespace Espo\Modules\Mcp\Tools\Mcp\Schema\Elicitation;

use Espo\Modules\Mcp\Tools\JsonSchema\ConstSchema;
use Espo\Modules\Mcp\Tools\JsonSchema\Schema;
use Espo\Modules\Mcp\Tools\Schema\Field\Traits\CommonTrait;
use stdClass;

class UntitledMultiSelectEnumSchema implements Schema
{
    use CommonTrait;

    /**
     * @param string[] $items
     * @param ?int<0, max> $minItems
     * @param ?int<0, max> $maxItems
     * @param string[] $default
     */
    public function __construct(
        private array $items,
        private ?int $minItems = null,
        private ?int $maxItems = null,
        private ?string $title = null,
        private ?string $description = null,
        private ?array $default = null,
    ) {}

    public function jsonSerialize(): stdClass
    {
        $object = (object) [
            'type' => 'array',
            'items' => (object) [
                'type' => 'string',
                'enum' => $this->items,
            ],
        ];

        if ($this->minItems !== null) {
            $object->minItems = $this->minItems;
        }

        if ($this->maxItems !== null) {
            $object->maxItems = $this->maxItems;
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
