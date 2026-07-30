<?php
/**LICENSE**/

namespace Espo\Modules\Mcp\Tools\Mcp;

use Espo\Core\Binding\BindingContainerBuilder;
use Espo\Core\InjectableFactory;
use Espo\Modules\Mcp\Entities\McpEndpoint;
use Espo\Modules\Mcp\Tools\Mcp\Handlers\ServerDiscoverHandler;

class RouterProvider
{
    public function __construct(
        private InjectableFactory $injectableFactory,
    ) {}

    public function get(McpEndpoint $endpoint): Router
    {
        $binding = BindingContainerBuilder::create()
            ->bindInstance(McpEndpoint::class, $endpoint)
            ->build();

        $router = $this->injectableFactory->createWithBinding(Router::class, $binding);

        $router->register(Method::SERVER_DISCOVER, ServerDiscoverHandler::class);
    }
}
