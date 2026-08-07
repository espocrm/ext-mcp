<?php
/**LICENSE**/

namespace Espo\Modules\Mcp\Tools\JsonSchema;

use Espo\Modules\Mcp\Tools\Schema\Field\Traits\CommonTrait;
use stdClass;

class EnumSchema implements Schema
{
    use CommonTrait;

    /**
     * @param array<int, scalar|null|stdClass|(stdClass|scalar|null)[]> $values
     */
    public function __construct(
        private array $values,
        private ?string $title = null,
        private ?string $description = null,
    ) {}

    public function jsonSerialize(): stdClass
    {
        $object = (object) [
            'enum' => array_map(function ($item) {
                return $item;
            }, $this->values),
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
