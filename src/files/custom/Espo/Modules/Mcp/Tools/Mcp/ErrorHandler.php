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
use Espo\Core\Api\ResponseComposer;
use Espo\Core\Utils\Log;
use Espo\Modules\Mcp\Tools\Mcp\Exceptions\InternalError;

class ErrorHandler
{
    private const string LOG_LEVEL_WARNING = 'warning';
    private const string LOG_LEVEL_INFO = 'info';

    public function __construct(
        private Log $log,
    ) {}

    public function handle(Request $request, Exceptions\Error $exception): Response
    {
        $this->log($exception);

        $error = [
            'code' => $exception->getRpcCode(),
            'message' => $exception->getMessage(),
        ];

        if ($exception->getData() !== null) {
            $error['data'] = $exception->getData();
        }

        $response = ResponseComposer::json([
            'jsonrpc' => JsonRpc::VERSION_2_0,
            'id' => $request->getParsedBody()->id ?? null,
            'error' => $error,
        ]);

        $response->setStatus($exception->getHttpCode());

        return $response;
    }

    private function log(Exceptions\Error $exception): void
    {
        $code = $exception->getRpcCode();

        $level = self::LOG_LEVEL_INFO;

        if ($exception instanceof InternalError) {
            $level = self::LOG_LEVEL_WARNING;
        }

        $this->log->log($level, "MCP. {$exception->getMessage()}; $code", ['exception' => $exception]);
    }
}
