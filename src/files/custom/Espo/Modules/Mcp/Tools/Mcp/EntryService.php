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
     */
    public function process(string $slug, Request $request): Response
    {
        $endpoint = $this->endpointProvider->get($slug);

        $router = $this->routerProvider->get($endpoint);

        try {
            $response = $router->dispatch($request);
        } catch (Exceptions\Error $e) {
            return $this->errorHandler->handle($request, $e);
        }

        return $response;
    }
}
