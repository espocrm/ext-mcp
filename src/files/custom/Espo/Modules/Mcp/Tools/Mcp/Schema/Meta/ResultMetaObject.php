<?php
/**LICENSE**/

namespace Espo\Modules\Mcp\Tools\Mcp\Schema\Meta;

use JsonSerializable;
use stdClass;

readonly class ResultMetaObject implements JsonSerializable
{
    public function __construct(
        public ?Implementation $serverInfo,
    ) {}

    public function jsonSerialize(): stdClass
    {
        $object = (object) [];

        if ($this->serverInfo) {
            $object->{"io.modelcontextprotocol/serverInfo"} = $this->serverInfo->jsonSerialize();
        }

        return $object;
    }
}
