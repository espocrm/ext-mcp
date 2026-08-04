<?php
/**LICENSE**/

namespace Espo\Modules\Mcp\Tools\Mcp\Schema\Tool;

use Espo\Modules\Mcp\Tools\Mcp\Schema\Value\CacheScope;
use Espo\Modules\Mcp\Tools\Mcp\Schema\Value\ResultType;
use JsonSerializable;
use stdClass;

readonly class ListToolsResult implements JsonSerializable
{
    /**
     * @param Tool[] $tools
     */
    public function __construct(
        public array $tools,
        public ResultType $resultType = ResultType::Complete,
        public int $ttlMs = 0,
        public CacheScope $cacheScope = CacheScope::Private,
    ) {}

    public function jsonSerialize(): stdClass
    {
        return (object) [
            'resultType' => $this->resultType->value,
            'tools' => array_map(fn (Tool $it) => $it->jsonSerialize(), $this->tools),
            'ttlMs' => $this->ttlMs,
            'cacheScope' => $this->cacheScope->value,
        ];
    }
}
