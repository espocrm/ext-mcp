<?php
/**LICENSE**/

namespace Espo\Modules\Mcp\Tools\Mcp\Hooks;

use Espo\Core\Api\Request;
use Espo\Modules\Mcp\Tools\Mcp\Exceptions\InvalidRequestError;
use Espo\Modules\Mcp\Tools\Mcp\Hook;
use Espo\Modules\Mcp\Tools\Mcp\JsonRpc;

class RequestValidityCheck implements Hook
{
    public function process(Request $request): void
    {
        if ($request->getMethod() !== 'POST') {
            throw new InvalidRequestError("Non-POST request.");
        }

        $jsonrpc = $request->getParsedBody()->jsonrpc ?? null;

        if ($jsonrpc !== JsonRpc::VERSION_2_0) {
            throw new InvalidRequestError("JSON-RPC version is not 2.0.");
        }
    }
}
