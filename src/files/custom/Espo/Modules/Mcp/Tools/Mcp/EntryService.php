<?php
/**LICENSE**/

namespace Espo\Modules\Mcp\Tools\Mcp;

use Espo\Core\Api\Request;
use Espo\Core\Api\Response;
use Espo\Core\Api\ResponseComposer;
use Espo\Core\Exceptions\BadRequest;
use Espo\Core\Exceptions\Forbidden;
use Espo\Core\Exceptions\NotFound;

class EntryService
{
    public function __construct(
        private EndpointProvider $endpointProvider,
        private RouterProvider $routerProvider,
        private ErrorHandler $errorHandler,
    ) {}

    /**
     * @throws Forbidden
     * @throws NotFound
     * @throws BadRequest
     */
    public function process(string $slug, Request $request): Response
    {
        $endpoint = $this->endpointProvider->get($slug);

        $router = $this->routerProvider->get($endpoint);

        try {
            $router->dispatch($request);
        } catch (Exceptions\Error $e) {
            return $this->errorHandler->handle($request, $e);
        }

        return ResponseComposer::json([]);
    }
}
