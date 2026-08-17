<?php
/**LICENSE**/

namespace Espo\Modules\Mcp\Tools\Mcp\Schema\Elicitation;

use Espo\Modules\Mcp\Tools\JsonSchema\ConstSchema;
use Espo\Modules\Mcp\Tools\JsonSchema\Schema;
use Espo\Modules\Mcp\Tools\Schema\Field\Traits\CommonTrait;
use stdClass;

class TitledSingleSelectEnumSchema implements Schema
{
    use CommonTrait;

    /**
     * @param (ConstSchema<string>)[] $items Descriptions are omitted.
     */
    public function __construct(
        private array $items,
        private ?string $title = null,
        private ?string $description = null,
        private ?string $default = null,
    ) {}

    public function jsonSerialize(): stdClass
    {
        $object = (object) [
            'type' => 'string',
            'oneOf' => array_map(function ($it) {
                return (object) [
                    'value' => $it->getValue(),
                    'title' => $it->getTitle(),
                ];
            }, $this->items)
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
