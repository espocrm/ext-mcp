<?php
/**LICENSE**/

namespace Espo\Modules\Mcp\Tools\Mcp\Hooks;

use Espo\Core\Api\Request;
use Espo\Modules\Mcp\Tools\Mcp\Exceptions\UnsupportedProtocolVersionError;
use Espo\Modules\Mcp\Tools\Mcp\Hook;
use Espo\Modules\Mcp\Tools\Mcp\SupportedVersionsProvider;

class ProtocolVersionCheck implements Hook
{
    public function __construct(
        private SupportedVersionsProvider $supportedVersionsProvider,
    ) {}

    public function process(Request $request): void
    {
        $requestedVersion = $request->getHeader('MCP-Protocol-Version') ?? '1900-01-01';

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
