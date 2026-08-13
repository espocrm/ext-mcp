<?php
/**LICENSE**/

namespace Espo\Modules\Mcp\Tools\Mcp\Schema\Meta;

use JsonSerializable;
use stdClass;

readonly class Implementation implements JsonSerializable
{
    public function __construct(
        public string $name,
        public string $version,
        public ?string $title = null,
        public ?string $description = null,
    ) {}

    public function jsonSerialize(): stdClass
    {
        $object = (object) [
            'name' => $this->name,
            'version' => $this->version,
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
