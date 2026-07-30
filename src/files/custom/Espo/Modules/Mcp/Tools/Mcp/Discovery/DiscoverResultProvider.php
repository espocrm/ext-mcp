<?php
/**LICENSE**/

namespace Espo\Modules\Mcp\Tools\Mcp\Discovery;

use Espo\Modules\Mcp\Tools\Mcp\Schema\DiscoverResult;
use Espo\Modules\Mcp\Tools\Mcp\Schema\ServerCapabilities;
use Espo\Modules\Mcp\Tools\Mcp\Schema\ToolsCapability;
use Espo\Modules\Mcp\Tools\Mcp\SupportedVersionsProvider;

class DiscoverResultProvider
{
    private const int TTL_MS = 60 * 60 * 1000;

    public function __construct(
        private SupportedVersionsProvider $supportedVersionsProvider,
    ) {}

    public function get(): DiscoverResult
    {
        $capabilities = new ServerCapabilities(
            toolsCapability: new ToolsCapability(listChanged: false),
        );

        return new DiscoverResult(
            supportedVersions: $this->supportedVersionsProvider->get(),
            capabilities: $capabilities,
            ttlMs: self::TTL_MS,
        );
    }
}
