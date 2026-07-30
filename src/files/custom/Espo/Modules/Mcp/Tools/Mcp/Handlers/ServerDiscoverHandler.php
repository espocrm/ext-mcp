<?php
/**LICENSE**/

namespace Espo\Modules\Mcp\Tools\Mcp\Handlers;

use Espo\Core\Api\Request;
use Espo\Core\Api\Response;
use Espo\Core\Api\ResponseComposer;
use Espo\Modules\Mcp\Tools\Mcp\Discovery\DiscoverResultProvider;
use Espo\Modules\Mcp\Tools\Mcp\Handler;
use Espo\Modules\Mcp\Tools\Mcp\RequestIdFetcher;
use Espo\Modules\Mcp\Tools\Mcp\Schema\GenericResponse;

class ServerDiscoverHandler implements Handler
{
    public function __construct(
        private DiscoverResultProvider $resultProvider,
        private RequestIdFetcher $requestIdFetcher,
    ) {}

    public function handle(Request $request): Response
    {
        $response = new GenericResponse(
            id: $this->requestIdFetcher->fetch($request),
            result: $this->resultProvider->get(),
        );

        return ResponseComposer::json($response->jsonSerialize());
    }
}
