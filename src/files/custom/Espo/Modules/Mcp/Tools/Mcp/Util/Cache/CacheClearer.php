<?php
/**LICENSE**/

namespace Espo\Modules\Mcp\Tools\Mcp\Util\Cache;

use Espo\Core\Utils\File\Manager as FileManager;
use Espo\Modules\Mcp\Tools\Mcp\Tool\CacheKeyProvider;

/**
 * @todo Use clearing category against DataCache when implemented.
 */
class CacheClearer
{
    public function __construct(
        private FileManager $fileManager,
    ) {}

    public function clear(string $endpointId): void
    {
        $dir = CacheKeyProvider::CATEGORY . '/' . basename($endpointId);

        $this->fileManager->removeInDir($dir, true);
    }
}
