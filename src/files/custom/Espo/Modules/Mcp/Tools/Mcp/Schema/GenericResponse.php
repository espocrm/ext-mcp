<?php
/**LICENSE**/

namespace Espo\Modules\Mcp\Tools\Mcp\Schema;

use Espo\Modules\Mcp\Tools\Mcp\JsonRpc;
use JsonSerializable;
use stdClass;

readonly class GenericResponse implements JsonSerializable
{
    public function __construct(
        public string|int $id,
        public JsonSerializable $result,
    ) {}

    public function jsonSerialize(): stdClass
    {
        return (object) [
            'jsonrpc' => JsonRpc::VERSION_2_0,
            'id' => $this->id,
            'result' => $this->result->jsonSerialize(),
        ];
    }
}
