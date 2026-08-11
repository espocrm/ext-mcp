<?php
/**LICENSE**/

namespace Espo\Modules\Mcp\Tools\Mcp\Schema\Tool;

use InvalidArgumentException;
use stdClass;

readonly class CallToolRequestParams
{
    public function __construct(
        public string $name,
        public ?string $requestState = null,
        public mixed $inputResponses = null,
        public ?stdClass $arguments = null,
    ) {}

    /**
     * @throws InvalidArgumentException
     */
    public static function fromRaw(stdClass $raw): self
    {
        $name = $raw->name ?? null;
        $requestState = $raw->requestState ?? null;
        $inputResponses = $raw->inputResponses ?? null;
        $arguments = $raw->arguments ?? null;

        if (!is_string($name) || !$name) {
            throw new InvalidArgumentException();
        }

        if ($requestState !== null && !is_string($requestState)) {
            throw new InvalidArgumentException();
        }

        if ($arguments !== null && !$arguments instanceof stdClass) {
            throw new InvalidArgumentException();
        }

        return new self(
            name: $name,
            requestState: $requestState,
            inputResponses: $inputResponses,
            arguments: $arguments,
        );
    }
}
