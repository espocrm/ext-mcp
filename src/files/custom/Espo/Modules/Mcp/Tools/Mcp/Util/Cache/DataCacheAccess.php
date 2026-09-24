<?php
/**LICENSE**/

namespace Espo\Modules\Mcp\Tools\Mcp\Util\Cache;

use Closure;
use Espo\Core\Utils\Config\SystemConfig;
use Espo\Core\Utils\DataCache;
use Espo\Core\Utils\Log;
use LogicException;
use RuntimeException;
use stdClass;

/**
 * @internal
 * @since 10.1.0
 * @template T of mixed = mixed
 */
class DataCacheAccess
{
    /** @var T|null  */
    private mixed $data = null;

    private ?string $key = null;

    /** @var (Closure(): T)|null  */
    private ?Closure $loader = null;

    /** @var (Closure(T): bool)|null  */
    private ?Closure $validityChecker = null;

    public function __construct(
        private DataCache $dataCache,
        private SystemConfig $systemConfig,
        private Log $log,
    ) {}

    /**
     * @param Closure(): T $loader
     * @param (Closure(T): bool)|null $validityChecker
     */
    public function init(string $key, Closure $loader, ?Closure $validityChecker = null): void
    {
        $this->key = $key;
        $this->loader = $loader;
        $this->validityChecker = $validityChecker;

        $this->data = null;
    }

    public function reset(): void
    {
        $this->data = null;
    }

    /**
     * @return T
     */
    public function get(): mixed
    {
        if ($this->data && $this->validityChecker && !($this->validityChecker)($this->data)) {
            $this->data = null;
        }

        if ($this->data !== null) {
            return $this->data;
        }

        $key = $this->key;
        $loader = $this->loader;

        if (!$key || !$loader) {
            throw new LogicException("Not initialized.");
        }

        if ($this->systemConfig->useCache() && $this->dataCache->has($key)) {
            $this->loadFromCache();
        }

        if ($this->data === null) {
            $this->data = $loader();

            if ($this->systemConfig->useCache()) {
                $serialized = serialize($this->data);

                $this->dataCache->store($key, (object) [
                    'data' => $serialized,
                ]);
            }
        }

        return $this->data;
    }

    private function loadFromCache(): void
    {
        $key = $this->key ?? throw new LogicException();

        try {
            $stored = $this->dataCache->get($key);
        } catch (RuntimeException $e) {
            $this->log->warning("Corrupted cache data by key '{key}'.", [
                'exception' => $e,
                'key' => $key,
            ]);

            $this->dataCache->clear($key);

            return;
        }

        if ($stored === null) {
            return;
        }

        if (!$stored instanceof stdClass) {
            return;
        }

        if (!property_exists($stored, 'data')) {
            return;
        }

        $data = unserialize($stored->data);

        /** @var T $data */

        if ($this->validityChecker && !($this->validityChecker)($data)) {
            $this->data = null;

            return;
        }

        $this->data = $data;
    }
}
