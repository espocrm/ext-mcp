<?php
/**LICENSE**/

namespace Espo\Modules\Mcp\Tools\JsonSchema\Type;

use Espo\Modules\Mcp\Tools\JsonSchema\Schema;
use stdClass;

class ObjectType implements Schema
{
    /**
     * @param array<string, Schema> $properties
     * @param string[] $required,
     */
    public function __construct(
        private array $properties = [],
        private array $required = [],
        private ?bool $additionalProperties = null,
        private ?string $title = null,
        private ?string $description = null,
    ) {}

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

        if ($this->title !== null) {
            $object->title = $this->title;
        }

        if ($this->description !== null) {
            $object->description = $this->description;
        }

        return $object;
    }
}
