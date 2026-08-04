<?php
/**LICENSE**/

namespace Espo\Modules\Mcp\Tools\Mcp\Schema\Discovery;

use Espo\Modules\Mcp\Tools\Mcp\Schema\Value\CacheScope;
use Espo\Modules\Mcp\Tools\Mcp\Schema\Value\ResultType;
use JsonSerializable;
use stdClass;

readonly class DiscoverResult implements JsonSerializable
{
    /**
     * @param string[] $supportedVersions
     */
    public function __construct(
        public array $supportedVersions,
        public ServerCapabilities $capabilities,
        public ResultType $resultType = ResultType::Complete,
        public ?string $instructions = null,
        public int $ttlMs = 0,
        public CacheScope $cacheScope = CacheScope::Private,
    ) {}

    public function jsonSerialize(): stdClass
    {
        $object = (object) [
            'resultType' => $this->resultType->value,
            'supportedVersions' => $this->supportedVersions,
            'capabilities' => $this->capabilities->jsonSerialize(),
            'ttlMs' => $this->ttlMs,
            'cacheScope' => $this->cacheScope->value,
        ];

        if ($this->instructions) {
            $object->instructions = $this->instructions;
        }

        return $object;
    }
}
