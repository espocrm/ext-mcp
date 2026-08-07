<?php
/**LICENSE**/

namespace Espo\Modules\Mcp\Tools\JsonSchema;

use Espo\Modules\Mcp\Tools\Schema\Field\Traits\CommonTrait;
use stdClass;

class ConstSchema implements Schema
{
    use CommonTrait;

    /**
     * @param scalar|stdClass|stdClass[]|scalar[]|null $value
     */
    public function __construct(
        private mixed $value,
        private ?string $title = null,
        private ?string $description = null,
    ) {}

    public function jsonSerialize(): stdClass
    {
        $object = (object) [
            'const' => $this->value,
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
