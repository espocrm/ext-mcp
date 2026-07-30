<?php
/**LICENSE**/

namespace Espo\Modules\Mcp\Tools\Mcp\Handlers;

use Espo\Core\Api\Request;
use Espo\Core\Api\Response;
use Espo\Modules\Mcp\Entities\McpEndpoint;
use Espo\Modules\Mcp\Tools\Mcp\Handler;

class ServerDiscoverHandler implements Handler
{
    public function __construct(
        private McpEndpoint $endpoint,
    ) {}

    public function handle(Request $request): Response
    {
        // TODO: Implement handle() method.
    }
}
