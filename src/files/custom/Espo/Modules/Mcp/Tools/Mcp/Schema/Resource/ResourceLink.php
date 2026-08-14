<?php
/**LICENSE**/

namespace Espo\Modules\Mcp\Tools\Mcp\Schema\Resource;

use Espo\Modules\Mcp\Tools\Mcp\Schema\Common\Annotations;
use JsonSerializable;
use stdClass;

readonly class ResourceLink implements JsonSerializable
{
    /**
     * @param string $name Intended for programmatic or logical use.
     * @param ?int $size Size in bytes.
     * @param ?Annotations $annotations
     */
    public function __construct(
        public string $name,
        public string $uri,
        public ?string $title = null,
        public ?string $description = null,
        public ?string $mimeType = null,
        public ?int $size = null,
        public ?Annotations $annotations = null,
    ) {}

    public function jsonSerialize(): stdClass
    {
        $object = (object) [
            'type' => 'resource_link',
            'name' => $this->name,
            'uri' => $this->uri,
        ];

        if ($this->title !== null) {
            $object->title = $this->title;
        }

        if ($this->description !== null) {
            $object->description = $this->description;
        }

        if ($this->mimeType !== null) {
            $object->mimeType = $this->mimeType;
        }

        if ($this->size !== null) {
            $object->size = $this->size;
        }

        if ($this->annotations !== null) {
            $object->annotations = $this->annotations->jsonSerialize();
        }

        return $object;
    }
}
