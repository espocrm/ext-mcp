<?php
/**LICENSE**/

namespace Espo\Modules\Mcp\Tools\JsonSchema\Type;

use Espo\Modules\Mcp\Tools\JsonSchema\Schema;
use Espo\Modules\Mcp\Tools\JsonSchema\StringFormat;
use stdClass;

class StringType implements Schema
{
    public function __construct(
        private ?int $minLength = null,
        private ?int $maxLength = null,
        private ?string $pattern = null,
        private ?StringFormat $format = null,
        private ?string $title = null,
        private ?string $description = null,
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

        if ($this->pattern !== null) {
            $object->pattern = $this->pattern;
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

        return $object;
    }
}
