<?php
/**LICENSE**/

namespace Espo\Modules\Mcp\Tools\JsonSchema;

use Espo\Modules\Mcp\Tools\Schema\Field\Traits\CommonTrait;
use stdClass;

class GroupSchema implements Schema
{
    use CommonTrait;

    /**
     * @param Schema[] $schemas
     * @param scalar|stdClass|stdClass[]|scalar[]|null $default
     */
    public function __construct(
        private GroupKeyword $keyword,
        private array $schemas,
        private ?string $title = null,
        private ?string $description = null,
        private mixed $default = null,
    ) {}

    /**
     * @param Schema[] $schemas
     * @param scalar|stdClass|stdClass[]|scalar[]|null $default
     */
    public static function createAnyOf(
        array $schemas,
        ?string $title = null,
        ?string $description = null,
        mixed $default = null,
    ): self {

        return new self(
            keyword: GroupKeyword::anyOf,
            schemas: $schemas,
            title: $title,
            description: $description,
            default: $default,
        );
    }

    public function jsonSerialize(): stdClass
    {
        $object = (object) [
            $this->keyword->value => array_map(fn ($item) => $item->jsonSerialize(), $this->schemas),
        ];

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
