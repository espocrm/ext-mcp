<?php
/************************************************************************
* This file is part of MCP extension for EspoCRM.
*
* MCP extension for EspoCRM.
* Copyright (C) 2026 EspoCRM, Inc.
* Website: https://www.espocrm.com
*
* This program is free software: you can redistribute it and/or modify
* it under the terms of the GNU Affero General Public License as published by
* the Free Software Foundation, either version 3 of the License, or
* (at your option) any later version.
*
* This program is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
* GNU Affero General Public License for more details.
*
* You should have received a copy of the GNU Affero General Public License
* along with this program. If not, see <https://www.gnu.org/licenses/>.
*
* The interactive user interfaces in modified source and object code versions
* of this program must display Appropriate Legal Notices, as required under
* Section 5 of the GNU Affero General Public License version 3.
*
* In accordance with Section 7(b) of the GNU Affero General Public License version 3,
* these Appropriate Legal Notices must retain the display of the "EspoCRM" word.
************************************************************************/

namespace Espo\Modules\Mcp\Tools\Mcp;

use Espo\Core\Api\Request;
use Espo\Core\Api\Response;
use Espo\Core\InjectableFactory;
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
        private BindingProvider $bindingProvider,
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
        $this->beforeHooks = [...$this->beforeHooks, ...$hooks];
    }

    /**
     * @param class-string<Handler> $handlerClassName
     */
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
        $handlerClass = $this->handlers[$method] ?? throw new MethodNotFoundError("Method `$method` is not supported.");

        $binding = $this->bindingProvider->get();

        return $this->injectableFactory->createWithBinding($handlerClass, $binding);
    }

    private function processBeforeHooks(Request $request): void
    {
        $binding = $this->bindingProvider->get();

        foreach ($this->beforeHooks as $hookClassName) {
            $hook = $this->injectableFactory->createWithBinding($hookClassName, $binding);

            $hook->process($request);
        }
    }
}
