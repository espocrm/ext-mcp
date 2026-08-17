<?php
/**LICENSE**/

namespace Espo\Modules\Mcp\Tools\Mcp\Schema\Elicitation;

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
