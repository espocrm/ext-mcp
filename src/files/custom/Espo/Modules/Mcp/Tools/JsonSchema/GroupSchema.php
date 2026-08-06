<?php
/**LICENSE**/

namespace Espo\Modules\Mcp\Tools\JsonSchema;

use stdClass;

class GroupSchema extends Schema
{
    /**
     * @param Schema[] $schemas
     */
    public function __construct(
        private GroupKeyword $keyword,
        private array $schemas,
        private ?string $title = null,
        private ?string $description = null,
    ) {}

    /**
     * @param Schema[] $schemas
     */
    public static function createAnyOf(
        array $schemas,
        ?string $title = null,
        ?string $description = null,
    ): self {

        return new self(
            keyword: GroupKeyword::anyOff,
            schemas: $schemas,
            title: $title,
            description: $description,
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

        return $object;
    }
}
