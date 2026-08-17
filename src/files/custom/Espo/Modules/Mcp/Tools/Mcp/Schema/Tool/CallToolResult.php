<?php
/**LICENSE**/

namespace Espo\Modules\Mcp\Tools\Mcp\Schema\Tool;

use Espo\Modules\Mcp\Tools\Mcp\Schema\Elicitation\ElicitRequest;
use Espo\Modules\Mcp\Tools\Mcp\Schema\Resource\ResourceLink;
use Espo\Modules\Mcp\Tools\Mcp\Schema\Value\ResultType;
use JsonSerializable;
use stdClass;

/**
 * @todo Support more content types.
 */
readonly class CallToolResult implements JsonSerializable
{
    /**
     * @param stdClass|stdClass[]|(scalar|null)[]|scalar|null $structuredContent
     * @param ResourceLink[] $content
     * @param ?array<string, ElicitRequest> $inputRequests
     */
    public function __construct(
        public ResultType $resultType = ResultType::Complete,
        public mixed $structuredContent = null,
        public array $content = [],
        public ?array $inputRequests = null,
        public ?bool $isError = null,
    ) {}

    public function jsonSerialize(): stdClass
    {
        $object = (object) [
            'resultType' => $this->resultType->value,
            'content' => array_map(fn ($it) => $it->jsonSerialize(), $this->content),
        ];

        if ($this->isError !== null) {
            $object->isError = $this->isError;
        }

        if ($this->structuredContent !== null) {
            $object->structuredContent = $this->structuredContent;
        }

        if ($this->inputRequests) {
            $object->inputRequests = (object) array_map(fn ($it) => $it->jsonSerialize(), $this->inputRequests);
        }

        return $object;
    }
}
