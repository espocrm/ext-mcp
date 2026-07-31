<?php
/**LICENSE**/

namespace Espo\Modules\Mcp\Tools\Mcp;

use Espo\Core\Api\Request;
use Espo\Core\Api\Response;
use Espo\Core\Binding\BindingContainer;
use Espo\Core\Binding\BindingContainerBuilder;
use Espo\Core\InjectableFactory;
use Espo\Modules\Mcp\Entities\Endpoint;
use Espo\Modules\Mcp\Tools\Mcp\Exceptions\Error;
use Espo\Modules\Mcp\Tools\Mcp\Exceptions\InvalidRequestError;
use Espo\Modules\Mcp\Tools\Mcp\Exceptions\MethodNotFoundError;

class Router
{
    /**
     * @var array<string, class-string<Handler>>
     */
    private array $handlers = [];

    /**
     * @var class-string<Hook>[]
     */
    private array $beforeHooks = [];

    public function __construct(
        private InjectableFactory $injectableFactory,
        private Endpoint $endpoint,
    ) {}

    /**
     * @param array<string, class-string<Handler>> $map
     */
    public function registerMultiple(array $map): void
    {
        foreach ($map as $method => $className) {
            $this->register($method, $className);
        }
    }

    /**
     * @param class-string<Hook>[] $hooks
     */
    public function registerBeforeHooks(array $hooks): void
    {
        $this->beforeHooks = [$this->beforeHooks, ...$hooks];
    }

    private function register(string $method, string $handlerClassName): void
    {
        $this->handlers[$method] = $handlerClassName;
    }

    /**
     * @throws Error
     */
    public function dispatch(Request $request): Response
    {
        $this->processBeforeHooks($request);

        $method = $request->getParsedBody()->method ?? throw new InvalidRequestError("No method.");

        $handler = $this->prepareHandler($method);

        return $handler->handle($request);
    }

    /**
     * @throws MethodNotFoundError
     */
    private function prepareHandler(string $method): Handler
    {
        $handlerClass = $this->handlers[$method] ?? throw new MethodNotFoundError();

        $binding = $this->prepareBinding();

        return $this->injectableFactory->createWithBinding($handlerClass, $binding);
    }

    private function processBeforeHooks(Request $request): void
    {
        $binding = $this->prepareBinding();

        foreach ($this->beforeHooks as $hookClassName) {
            $hook = $this->injectableFactory->createWithBinding($hookClassName, $binding);

            $hook->process($request);
        }
    }

    private function prepareBinding(): BindingContainer
    {
        return BindingContainerBuilder::create()
            ->bindInstance(Endpoint::class, $this->endpoint)
            ->build();
    }
}
