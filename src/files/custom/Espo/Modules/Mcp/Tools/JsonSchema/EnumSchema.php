<?php
/**LICENSE**/

namespace Espo\Modules\Mcp\Tools\JsonSchema;

use stdClass;

class EnumSchema extends Schema
{
    /**
     * @param array<int, scalar|null|Schema> $values
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
                if ($item instanceof Schema) {
                    $item->jsonSerialize();
                }

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
