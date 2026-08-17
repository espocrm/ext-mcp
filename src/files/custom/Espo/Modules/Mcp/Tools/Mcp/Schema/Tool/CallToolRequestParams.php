<?php
/**LICENSE**/

namespace Espo\Modules\Mcp\Tools\Mcp\Schema\Tool;

use Espo\Modules\Mcp\Tools\Mcp\Schema\Elicitation\ElicitResult;
use InvalidArgumentException;
use stdClass;

readonly class CallToolRequestParams
{
    /**
     * @param string $name
     * @param string|null $requestState
     * @param ?array<string, ElicitResult> $inputResponses
     * @param stdClass|null $arguments
     */
    public function __construct(
        public string $name,
        public ?string $requestState = null,
        public ?array $inputResponses = null,
        public ?stdClass $arguments = null,
    ) {}

    /**
     * @throws InvalidArgumentException
     */
    public static function fromRaw(stdClass $raw): self
    {
        $name = $raw->name ?? null;
        $requestState = $raw->requestState ?? null;
        $arguments = $raw->arguments ?? null;
        $inputResponsesRaw = $raw->inputResponses ?? null;

        if (!is_string($name) || !$name) {
            throw new InvalidArgumentException("No `name`.");
        }

        if ($requestState !== null && !is_string($requestState)) {
            throw new InvalidArgumentException("Bad `requestState` type.");
        }

        if ($arguments !== null && !$arguments instanceof stdClass) {
            throw new InvalidArgumentException("Bad `arguments` type.");
        }

        $inputResponses = null;

        if ($inputResponsesRaw !== null) {
            if (!$inputResponsesRaw instanceof stdClass) {
                throw new InvalidArgumentException("Bad `inputResponses`.");
            }

            $inputResponses = array_map(function ($it) {
                if (!$it instanceof stdClass) {
                    throw new InvalidArgumentException("Bad input response item.");
                }

                $action = $it->action ?? null;

                if ($action) {
                    return ElicitResult::fromRaw($it);
                }

                throw new InvalidArgumentException("Bad input response item.");
            }, get_object_vars($inputResponsesRaw));
        }

        return new self(
            name: $name,
            requestState: $requestState,
            inputResponses: $inputResponses,
            arguments: $arguments,
        );
    }
}
