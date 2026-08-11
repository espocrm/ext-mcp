<?php
/**LICENSE**/

namespace Espo\Modules\Mcp\Tools\Mcp\Tool;

use Espo\Modules\Mcp\Tools\Mcp\CachePropertyProvider;
use Espo\Modules\Mcp\Tools\Mcp\Exceptions\InternalError;
use Espo\Modules\Mcp\Tools\Mcp\Schema\Tool\ListToolsResult;

class ListToolsResultProvider
{
    public function __construct(
        private CachePropertyProvider $cachePropertyProvider,
        private ToolProvider $toolProvider,
    ) {}

    /**
     * @throws InternalError
     */
    public function get(): ListToolsResult
    {
        return new ListToolsResult(
            tools: $this->toolProvider->getAll(),
            ttlMs: $this->cachePropertyProvider->getGeneralTtlMs(),
        );
    }
}
