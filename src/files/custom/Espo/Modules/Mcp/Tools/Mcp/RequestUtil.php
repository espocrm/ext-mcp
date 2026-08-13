<?php
/**LICENSE**/

namespace Espo\Modules\Mcp\Tools\Mcp;

use Espo\Core\Api\Request;
use stdClass;

class RequestUtil
{
    public static function fetchMetaParam(Request $request, string $param): mixed
    {
        $meta = $request->getParsedBody()->_meta ?? null;

        if (!$meta instanceof stdClass) {
            return null;
        }

        return $meta->$param ?? null;
    }
}
