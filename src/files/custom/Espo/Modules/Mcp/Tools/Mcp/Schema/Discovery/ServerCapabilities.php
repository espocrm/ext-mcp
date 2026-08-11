<?php
/**LICENSE**/

namespace Espo\Modules\Mcp\Tools\Mcp\Schema\Discovery;

use JsonSerializable;
use stdClass;

readonly class ServerCapabilities implements JsonSerializable
{
    public function __construct(
        public ?PromptsCapability $prompts = null,
        public ?ToolsCapability $tools = null,
        public ?ResourcesCapability $resources = null,
    ) {}

    public function jsonSerialize(): stdClass
    {
        $object = (object) [];

        if ($this->prompts) {
            $object->prompts = $this->prompts->jsonSerialize();
        }

        if ($this->tools) {
            $object->tools = $this->tools->jsonSerialize();
        }

        if ($this->resources) {
            $object->resources = $this->resources->jsonSerialize();
        }

        return $object;
    }
}
