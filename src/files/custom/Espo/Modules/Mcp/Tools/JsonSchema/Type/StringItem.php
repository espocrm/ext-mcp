<?php
/**LICENSE**/

namespace Espo\Modules\Mcp\Tools\JsonSchema\Type;

use Espo\Modules\Mcp\Tools\JsonSchema\Item;
use stdClass;

class StringItem extends Item
{
    public function __construct(
        private ?string $description = null,
    ) {}

    public function jsonSerialize(): stdClass
    {
        $object = (object) [
            'type' => 'string',
        ];

        if ($this->description) {
            $object->description = $this->description;
        }

        return $object;
    }
}
