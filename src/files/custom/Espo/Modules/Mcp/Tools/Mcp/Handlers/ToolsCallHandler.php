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

namespace Espo\Modules\Mcp\Tools\Mcp\Handlers;

use Espo\Core\Api\Request;
use Espo\Core\Api\Response;
use Espo\Core\Api\ResponseComposer;
use Espo\Modules\Mcp\Tools\Mcp\Exceptions\InvalidRequestError;
use Espo\Modules\Mcp\Tools\Mcp\Handler;
use Espo\Modules\Mcp\Tools\Mcp\RequestIdFetcher;
use Espo\Modules\Mcp\Tools\Mcp\Schema\GenericResponseComposer;
use Espo\Modules\Mcp\Tools\Mcp\Schema\Tool\CallToolRequestParams;
use Espo\Modules\Mcp\Tools\Mcp\ToolsCall\ToolsCallGeneralProcessor;
use InvalidArgumentException;
use stdClass;

class ToolsCallHandler implements Handler
{
    public function __construct(
        private RequestIdFetcher $requestIdFetcher,
        private ToolsCallGeneralProcessor $generalProcessor,
        private GenericResponseComposer $genericResponseComposer,
    ) {}

    public function handle(Request $request): Response
    {
        $params = $this->fetchParams($request);

        $response = $this->genericResponseComposer->compose(
            id: $this->requestIdFetcher->fetch($request),
            result: $this->generalProcessor->process($params),
        );

        return ResponseComposer::json($response->jsonSerialize());
    }

    /**
     * @throws InvalidRequestError
     */
    private function fetchParams(Request $request): CallToolRequestParams
    {
        $paramsRaw = $request->getParsedBody()->params ?? null;

        if (!$paramsRaw instanceof stdClass) {
            throw new InvalidRequestError();
        }

        try {
            $params = CallToolRequestParams::fromRaw($paramsRaw);
        } catch (InvalidArgumentException $e) {
            throw new InvalidRequestError("Bad search params.", previous: $e);
        }

        return $params;
    }
}
