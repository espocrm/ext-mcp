<?php
/**LICENSE**/

namespace Espo\Modules\Mcp\Tools\Mcp;

class CachePropertyProvider
{
    private const int TTL_MS = 60 * 60 * 1000;

    public function getGeneralTtlMs(): int
    {
        return self::TTL_MS;
    }
}
