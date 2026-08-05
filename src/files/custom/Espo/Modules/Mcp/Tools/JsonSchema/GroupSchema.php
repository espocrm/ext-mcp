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
    ) {}

    public static function createAnyOf(array $schemas): self
    {
        return new self(
            keyword: GroupKeyword::anyOff,
            schemas: $schemas,
        );
    }

    public function jsonSerialize(): stdClass
    {
        return (object) [
            $this->keyword->value => array_map(fn ($item) => $item->jsonSerialize(), $this->schemas),
        ];
    }
}
