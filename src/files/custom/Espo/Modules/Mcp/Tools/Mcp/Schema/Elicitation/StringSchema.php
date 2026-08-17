<?php
/**LICENSE**/

namespace Espo\Modules\Mcp\Tools\Mcp\Schema\Elicitation;

use Espo\Modules\Mcp\Tools\JsonSchema\Schema;
use Espo\Modules\Mcp\Tools\Schema\Field\Traits\CommonTrait;
use stdClass;

class StringSchema implements Schema
{
    use CommonTrait;

    /**
     * @param ?int<0, max> $minLength
     * @param ?int<0, max> $maxLength
     */
    public function __construct(
        private ?int $minLength = null,
        private ?int $maxLength = null,
        private ?StringFormat $format = null,
        private ?string $title = null,
        private ?string $description = null,
        private ?string $default = null,
    ) {}

    public function jsonSerialize(): stdClass
    {
        $object = (object) [
            'type' => 'string',
        ];

        if ($this->minLength !== null) {
            $object->minLength = $this->minLength;
        }

        if ($this->maxLength !== null) {
            $object->maxLength = $this->maxLength;
        }

        if ($this->format) {
            $object->format = $this->format->value;
        }

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
