<?php
/**LICENSE**/

namespace Espo\Modules\Mcp\Tools\Mcp\Schema\Elicitation;

use InvalidArgumentException;
use JsonSerializable;
use stdClass;

readonly class ElicitResult implements JsonSerializable
{
    /**
     * @param ?array<string, string|int|float|bool|string[]> $content
     */
    public function __construct(
        public ElicitAction $action,
        public ?array $content = null,
    ) {}

    public static function fromRaw(mixed $raw): self
    {
        if (!$raw instanceof stdClass) {
            throw new InvalidArgumentException("Bad elicit result.");
        }

        $rawAction = $raw->action ?? null;

        if (!$rawAction) {
            throw new InvalidArgumentException("Bad elicit result action");
        }

        $action = ElicitAction::from($rawAction);

        $content = null;

        $rawContent = $raw->content ?? null;

        if ($rawContent !== null) {
            if (!$rawContent instanceof stdClass) {
                throw new InvalidArgumentException("Bad elicit result content.");
            }

            $content = get_object_vars($rawContent);

            foreach ($content as $item) {
                if (is_bool($item) || is_string($item) || is_float($item) || is_int($item)) {
                    continue;
                }

                if (!is_array($item)) {
                    throw new InvalidArgumentException("Bad elicit content item value.");
                }

                foreach ($item as $subItem) {
                    if (!is_string($subItem)) {
                        throw new InvalidArgumentException("Bad elicit content array item value.");
                    }
                }
            }
        }

        return new self(
            action: $action,
            content: $content,
        );
    }

    public function jsonSerialize(): stdClass
    {
        $object = (object) [
            'action' => $this->action->value,
        ];

        if ($this->content) {
            $object->content = (object) $this->content;
        }

        return $object;
    }
}
