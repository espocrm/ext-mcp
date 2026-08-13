<?php
/**LICENSE**/

namespace Espo\Modules\Mcp\Tools\Mcp\Handlers;

use Espo\Core\Api\Request;
use Espo\Core\Api\Response;
use Espo\Core\Api\ResponseComposer;
use Espo\Modules\Mcp\Tools\Mcp\Handler;
use Espo\Modules\Mcp\Tools\Mcp\RequestIdFetcher;
use Espo\Modules\Mcp\Tools\Mcp\Schema\GenericResponseComposer;
use Espo\Modules\Mcp\Tools\Mcp\Tool\ListToolsResultProvider;

class ToolsListHandler implements Handler
{
    public function __construct(
        private ListToolsResultProvider $resultProvider,
        private RequestIdFetcher $requestIdFetcher,
        private GenericResponseComposer $genericResponseComposer,
    ) {}

    public function handle(Request $request): Response
    {
        $response = $this->genericResponseComposer->compose(
            id: $this->requestIdFetcher->fetch($request),
            result: $this->resultProvider->get(),
        );

        return ResponseComposer::json($response->jsonSerialize());
    }
}
