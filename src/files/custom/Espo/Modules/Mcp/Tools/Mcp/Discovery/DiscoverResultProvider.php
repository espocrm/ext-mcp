<?php
/**LICENSE**/

namespace Espo\Modules\Mcp\Tools\Mcp\Discovery;

use Espo\Modules\Mcp\Tools\Mcp\CachePropertyProvider;
use Espo\Modules\Mcp\Tools\Mcp\Schema\Discovery\DiscoverResult;
use Espo\Modules\Mcp\Tools\Mcp\Schema\Discovery\ServerCapabilities;
use Espo\Modules\Mcp\Tools\Mcp\Schema\Discovery\ToolsCapability;
use Espo\Modules\Mcp\Tools\Mcp\SupportedVersionsProvider;

class DiscoverResultProvider
{
    public function __construct(
        private SupportedVersionsProvider $supportedVersionsProvider,
        private CachePropertyProvider $cachePropertyProvider,
    ) {}

    public function get(): DiscoverResult
    {
        $capabilities = new ServerCapabilities(
            tools: new ToolsCapability(listChanged: false),
        );

        return new DiscoverResult(
            supportedVersions: $this->supportedVersionsProvider->get(),
            capabilities: $capabilities,
            ttlMs: $this->cachePropertyProvider->getGeneralTtlMs(),
        );
    }
}
