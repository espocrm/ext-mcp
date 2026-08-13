<?php
/**LICENSE**/

namespace Espo\Modules\Mcp\Tools\Mcp\Hooks;

use Espo\Core\Api\Request;
use Espo\Modules\Mcp\Tools\Mcp\Exceptions\HeaderMismatchError;
use Espo\Modules\Mcp\Tools\Mcp\Exceptions\InvalidRequestError;
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
        $bodyRequestedVersion = RequestUtil::fetchMetaParam($request, 'io.modelcontextprotocol/protocolVersion');

        $requestedVersion = $request->getHeader('MCP-Protocol-Version');

        if ($requestedVersion !== null && $bodyRequestedVersion !== null) {
            if ($requestedVersion !== $bodyRequestedVersion) {
                throw new HeaderMismatchError("Protocol version in header does not match version in body.");
            }
        }

        if (!$requestedVersion) {
            throw new InvalidRequestError("No MCP-Protocol-Version header.");
        }

        $supportedVersions = $this->supportedVersionsProvider->get();

        if (!in_array($requestedVersion, $supportedVersions)) {
            throw UnsupportedProtocolVersionError::create(
                supported: $supportedVersions,
                requested: $requestedVersion,
                message: 'Unsupported protocol version.',
            );
        }
    }
}
