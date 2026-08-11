<?php
/**LICENSE**/

namespace Espo\Modules\Mcp\Tools\Mcp\Schema\Tool;

use Espo\Modules\Mcp\Tools\Mcp\Schema\Value\ResultType;
use JsonSerializable;
use stdClass;

/**
 * @todo Support `content`.
 */
readonly class CallToolResult implements JsonSerializable
{
    /**
     * @param stdClass|stdClass[]|(scalar|null)[]|scalar|null $structuredContent
     */
    public function __construct(
        public ResultType $resultType = ResultType::Complete,
        public mixed $structuredContent = null,
        public ?bool $isError = null,
    ) {}

    public function jsonSerialize(): stdClass
    {
        $object = (object) [
            'resultType' => $this->resultType->value,
            'content' => [],
        ];

        if ($this->isError !== null) {
            $object->isError = $this->isError;
        }

        if ($this->structuredContent !== null) {
            $object->structuredContent = $this->structuredContent;
        }

        return $object;
    }
}
