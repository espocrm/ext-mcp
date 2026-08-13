<?php
/**LICENSE**/

namespace Espo\Modules\Mcp\Tools\Mcp\Schema;

use Espo\Modules\Mcp\Tools\Mcp\JsonRpc;
use Espo\Modules\Mcp\Tools\Mcp\Schema\Meta\ResultMetaObject;
use JsonSerializable;
use stdClass;

readonly class GenericResponse implements JsonSerializable
{
    public function __construct(
        public string|int $id,
        public JsonSerializable $result,
        public ?ResultMetaObject $meta = null,
    ) {}

    public function jsonSerialize(): stdClass
    {
        $result = $this->result->jsonSerialize();

        if ($result instanceof stdClass && $this->meta) {
            $result->_meta = $this->meta->jsonSerialize();
        }

        return (object) [
            'jsonrpc' => JsonRpc::VERSION_2_0,
            'id' => $this->id,
            'result' => $result,
        ];
    }
}
