<?php
/**LICENSE**/

namespace Espo\Modules\Mcp\Tools\Mcp\Schema\Discovery;

use JsonSerializable;
use stdClass;

readonly class ServerCapabilities implements JsonSerializable
{
    public function __construct(
        public ?PromptsCapability $promptsCapability = null,
        public ?ToolsCapability $toolsCapability = null,
        public ?ResourcesCapability $resourcesCapability = null,
    ) {}

    public function jsonSerialize(): stdClass
    {
        $object = (object) [];

        if ($this->promptsCapability) {
            $object->promptsCapability = $this->promptsCapability->jsonSerialize();
        }

        if ($this->toolsCapability) {
            $object->toolsCapability = $this->toolsCapability->jsonSerialize();
        }

        if ($this->resourcesCapability) {
            $object->resourcesCapability = $this->resourcesCapability->jsonSerialize();
        }

        return $object;
    }
}
