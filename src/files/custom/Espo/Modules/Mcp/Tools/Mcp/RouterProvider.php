<?php
/**LICENSE**/

namespace Espo\Modules\Mcp\Tools\Mcp;

use Espo\Core\Binding\BindingContainerBuilder;
use Espo\Core\InjectableFactory;
use Espo\Modules\Mcp\Entities\Endpoint;
use Espo\Modules\Mcp\Tools\Mcp\Handlers\ServerDiscoverHandler;
use Espo\Modules\Mcp\Tools\Mcp\Hooks\ProtocolVersionCheck;
use Espo\Modules\Mcp\Tools\Mcp\Hooks\RequestValidityCheck;

class RouterProvider
{
    public function __construct(
        private InjectableFactory $injectableFactory,
    ) {}

    public function get(Endpoint $endpoint): Router
    {
        $binding = BindingContainerBuilder::create()
            ->bindInstance(Endpoint::class, $endpoint)
            ->build();

        $router = $this->injectableFactory->createWithBinding(Router::class, $binding);

        $this->register($router);

        return $router;
    }

    private function register(Router $router): void
    {
        $router->registerBeforeHooks([
            RequestValidityCheck::class,
            ProtocolVersionCheck::class,
        ]);

        $router->registerMultiple([
            Method::SERVER_DISCOVER => ServerDiscoverHandler::class,
        ]);
    }
}
