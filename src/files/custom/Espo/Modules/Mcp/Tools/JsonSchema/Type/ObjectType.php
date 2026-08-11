<?php
/**LICENSE**/

namespace Espo\Modules\Mcp\Tools\JsonSchema\Type;

use Espo\Modules\Mcp\Tools\JsonSchema\Schema;
use Espo\Modules\Mcp\Tools\Schema\Field\Traits\CommonTrait;
use stdClass;

class ObjectType implements Type
{
    use CommonTrait;

    /**
     * @param array<string, Schema> $properties
     * @param string[] $required,
     */
    public function __construct(
        private array $properties = [],
        private array $required = [],
        private Schema|bool|null $additionalProperties = null,
        private ?string $title = null,
        private ?string $description = null,
    ) {}

    public function jsonSerialize(): stdClass
    {
        $object = (object) [
            'type' => 'object',
        ];

        $properties = array_map(fn ($item) => $item->jsonSerialize(), $this->properties);

        if ($properties) {
            $object->properties = (object) $properties;
        }

        if ($this->required) {
            $object->required = $this->required;
        }

        if ($this->additionalProperties !== null) {
            $object->additionalProperties = is_bool($this->additionalProperties) ?
                $this->additionalProperties :
                $this->additionalProperties->jsonSerialize();
        } else {
            unset($object->additionalProperties);
        }

        if ($this->title !== null) {
            $object->title = $this->title;
        }

        if ($this->description !== null) {
            $object->description = $this->description;
        }

        return $object;
    }

    /**
     * @return array<string, Schema>
     */
    public function getProperties(): array
    {
        return $this->properties;
    }

    /**
     * @return string[]
     */
    public function getRequired(): array
    {
        return $this->required;
    }

    public function getAdditionalProperties(): Schema|bool|null
    {
        return $this->additionalProperties;
    }
}
