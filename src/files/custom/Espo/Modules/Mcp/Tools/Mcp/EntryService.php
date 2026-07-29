<?php
/**LICENSE**/

namespace Espo\Modules\Mcp\Tools\Mcp;

use Espo\Core\Exceptions\Forbidden;
use Espo\Core\Exceptions\NotFound;
use Psr\Http\Message\ResponseInterface;

class EntryService
{
    public function __construct(
        private EndpointProvider $endpointProvider,
    ) {}

    /**
     * @throws Forbidden
     * @throws NotFound
     */
    public function process(string $slug, \Espo\Core\Api\Request $request): ResponseInterface
    {
        $endpoint = $this->endpointProvider->get($slug);
    }
}
