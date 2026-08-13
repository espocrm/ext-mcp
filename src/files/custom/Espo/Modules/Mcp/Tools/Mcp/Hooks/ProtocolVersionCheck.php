<?php
/**LICENSE**/

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
