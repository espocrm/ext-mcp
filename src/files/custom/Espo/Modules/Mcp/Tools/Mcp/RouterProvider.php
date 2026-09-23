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

use Espo\Core\InjectableFactory;
use Espo\Modules\Mcp\Entities\Endpoint;
use Espo\Modules\Mcp\Tools\Mcp\Handlers\ServerDiscoverHandler;
use Espo\Modules\Mcp\Tools\Mcp\Handlers\ToolsCallHandler;
use Espo\Modules\Mcp\Tools\Mcp\Handlers\ToolsListHandler;
use Espo\Modules\Mcp\Tools\Mcp\Hooks\ProtocolVersionCheck;
use Espo\Modules\Mcp\Tools\Mcp\Hooks\RequestValidityCheck;

class RouterProvider
{
    public function __construct(
        private InjectableFactory $injectableFactory,
        private BindingPreparator $bindingPreparator,
    ) {}

    public function get(Endpoint $endpoint): Router
    {
        $binding = $this->bindingPreparator->prepare($endpoint);

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
            Method::TOOLS_LIST => ToolsListHandler::class,
            Method::TOOLS_CALL => ToolsCallHandler::class,
        ]);
    }
}
