<?php
/**LICENSE**/

namespace Espo\Modules\Mcp\Tools\Mcp;

use Espo\Core\Api\Request;
use Espo\Core\Api\Response;
use Espo\Core\Api\ResponseComposer;
use Espo\Core\Exceptions\Forbidden;
use Espo\Core\Exceptions\NotFound;

class EntryService
{
    public function __construct(
        private EndpointProvider $endpointProvider,
    ) {}

    /**
     * @throws Forbidden
     * @throws NotFound
     */
    public function process(string $slug, Request $request): Response
    {
        $endpoint = $this->endpointProvider->get($slug);

        return ResponseComposer::json([]);
    }
}
