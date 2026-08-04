<?php
/**LICENSE**/

namespace Espo\Modules\Mcp\Tools\Mcp\Schema\Tool;

use JsonSerializable;
use stdClass;

readonly class Tool implements JsonSerializable
{
    public function __construct(
        public string $name,
        public ObjectSchema $inputSchema,
        public ?ArbitrarySchema $outputSchema = null,
        public ?string $title = null,
        public ?string $description = null,
        public ?ToolAnnotations $annotations = null,
    ) {}

    public function jsonSerialize(): stdClass
    {
        $object = (object) [
            'name' => $this->name,
            'inputSchema' => $this->inputSchema->jsonSerialize(),
        ];

        if ($this->outputSchema) {
            $object['outputSchema'] = $this->outputSchema->jsonSerialize(),
        }

        if ($this->title !== null) {
            $object->title = $this->title;
        }

        if ($this->description !== null) {
            $object->description = $this->description;
        }

        if ($this->annotations) {
            $object->annotations = $this->annotations;
        }

        return $object;
    }
}
