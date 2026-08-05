<?php
/**LICENSE**/

namespace Espo\Modules\Mcp\Tools\JsonSchema\Type;

use Espo\Modules\Mcp\Tools\JsonSchema\Item;
use stdClass;

class ObjectItem extends Item
{
    /**
     * @param array<string, Item> $properties
     * @param string[] $required,
     */
    public function __construct(
        private array $properties = [],
        private array $required = [],
        private ?bool $additionalProperties = null,
        private ?string $description = null,
    ) {}

    public function withAdditionalProperties(?bool $additionalProperties): self
    {
        $object = clone $this;
        $object->additionalProperties = $additionalProperties;

        return $object;
    }

    /**
     * @param string[] $required
     */
    public function withRequired(array $required): self
    {
        $object = clone $this;
        $object->required = $required;

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
            'type' => 'object',
            'properties' => (object) array_map(fn ($item) => $item->jsonSerialize(), $this->properties),
            'additionalProperties' => $this->additionalProperties,
        ];

        if ($this->required) {
            $object->required = $this->required;
        }

        if ($this->additionalProperties !== null) {
            $object->additionalProperties = $this->additionalProperties;
        }

        if ($this->description) {
            $object->description = $this->description;
        }

        return $object;
    }
}
