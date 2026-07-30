<?php
/**LICENSE**/

namespace Espo\Modules\Mcp\Tools\Mcp\Hooks;

use Espo\Core\Api\Request;
use Espo\Modules\Mcp\Tools\Mcp\Exceptions\UnsupportedProtocolVersionError;
use Espo\Modules\Mcp\Tools\Mcp\Hook;

class ProtocolVersionCheckHook implements Hook
{
    /**
     * @var string[]
     */
    private array $supportedVersions = [
        '2026-07-28'
    ];

    public function process(Request $request): void
    {
        $requestedVersion = $request->getHeader('MCP-Protocol-Version') ?? '1900-01-01';

        if (!in_array($requestedVersion, $this->supportedVersions)) {
            throw UnsupportedProtocolVersionError::create(
                supported: $this->supportedVersions,
                requested: $requestedVersion,
                message: 'Unsupported protocol version.',
            );
        }
    }
}
