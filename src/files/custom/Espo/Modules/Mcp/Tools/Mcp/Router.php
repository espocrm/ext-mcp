<?php
/**LICENSE**/

namespace Espo\Modules\Mcp\Tools\Mcp;

use Espo\Core\Api\Request;
use Espo\Core\Api\Response;
use Espo\Core\Binding\BindingContainerBuilder;
use Espo\Core\Exceptions\BadRequest;
use Espo\Core\InjectableFactory;
use Espo\Modules\Mcp\Entities\McpEndpoint;
use Espo\Modules\Mcp\Tools\Mcp\Exceptions\MethodNotFoundError;

class Router
{
    /**
     * @var array<string, class-string<Handler>>
     */
    private array $handlers = [];

    public function __construct(
        private InjectableFactory $injectableFactory,
        private McpEndpoint $endpoint,
    ) {}

    public function register(string $method, string $handlerClassName): void
    {
        $this->handlers[$method] = $handlerClassName;
    }

    /**
     * @throws BadRequest
     * @throws MethodNotFoundError
     */
    public function dispatch(Request $request): Response
    {
        if ($request->getMethod() !== 'POST') {
            throw new BadRequest();
        }

        $method = $request->getParsedBody()->method ?? throw new BadRequest("No method.");

        $handler = $this->prepareHandler($method);

        return $handler->handle($request);
    }

    /**
     * @throws MethodNotFoundError
     */
    private function prepareHandler($method): Handler
    {
        $handlerClass = $this->handlers[$method] ?? throw new MethodNotFoundError();

        $binding = BindingContainerBuilder::create()
            ->bindInstance(McpEndpoint::class, $this->endpoint)
            ->build();

        return $this->injectableFactory->createWithBinding($handlerClass, $binding);
    }
}
