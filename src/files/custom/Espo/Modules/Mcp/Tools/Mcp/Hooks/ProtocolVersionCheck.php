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

namespace Espo\Modules\Mcp\Tools\Mcp\Hooks;

use Espo\Core\Api\Request;
use Espo\Modules\Mcp\Tools\Mcp\Exceptions\HeaderMismatchError;
use Espo\Modules\Mcp\Tools\Mcp\Exceptions\UnsupportedProtocolVersionError;
use Espo\Modules\Mcp\Tools\Mcp\Hook;
use Espo\Modules\Mcp\Tools\Mcp\RequestUtil;
use Espo\Modules\Mcp\Tools\Mcp\SupportedVersionsProvider;

class ProtocolVersionCheck implements Hook
{
    public function __construct(
        private SupportedVersionsProvider $supportedVersionsProvider,
    ) {}

    public function process(Request $request): void
    {
        $bodyVersion = RequestUtil::fetchMetaParam($request, 'io.modelcontextprotocol/protocolVersion');

        $headerVersion = $request->getHeader('MCP-Protocol-Version');

        if ($headerVersion !== null && $bodyVersion !== null && $headerVersion !== $bodyVersion) {
            $message = "Protocol version in header does not match version in body.";

            $message = $this->prepareErrorMessage($message, $request);

            throw new HeaderMismatchError($message);
        }

        if (!$headerVersion) {
            $message = "No MCP-Protocol-Version header.";

            $message = $this->prepareErrorMessage($message, $request);

            throw new HeaderMismatchError($message);
        }

        $supportedVersions = $this->supportedVersionsProvider->get();

        if (!in_array($headerVersion, $supportedVersions)) {
            throw UnsupportedProtocolVersionError::create(
                supported: $supportedVersions,
                requested: $headerVersion,
                message: 'Unsupported protocol version.',
            );
        }
    }

    private function prepareErrorMessage(string $message, Request $request): string
    {
        $method = $request->getParsedBody()->method ?? null;

        if ($method === 'initialize') {
            $versionsString = implode(', ', $this->supportedVersionsProvider->get());

            $message .= " Supported protocol versions: $versionsString.";
        }

        return $message;
    }
}
