<?php
/**LICENSE**/

namespace Espo\Modules\Mcp\Tools\Schema\Field\Traits;

trait CommonTrait
{
    private mixed $default = null;

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function withTitle(?string $title): static
    {
        $object = clone $this;
        $object->title = $title;

        return $object;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function withDescription(?string $description): static
    {
        $object = clone $this;
        $object->description = $description;

        return $object;
    }

    public function getDefault(): mixed
    {
        return $this->default;
    }

    public function withDefault(mixed $default): static
    {
        $object = clone $this;
        $object->default = $default;

        return $object;
    }
}
