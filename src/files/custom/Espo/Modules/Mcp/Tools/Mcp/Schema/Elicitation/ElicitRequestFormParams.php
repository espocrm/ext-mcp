<?php
/**LICENSE**/

namespace Espo\Modules\Mcp\Tools\Mcp\Schema\Elicitation;

use Espo\Modules\Mcp\Tools\Mcp\Schema\General\RootObjectSchema;
use JsonSerializable;
use stdClass;

/**
 * @phpstan-type PrimitiveSchema StringSchema|NumberSchema|IntegerSchema|BooleanSchema
 * @phpstan-type SingleEnumSchema TitledSingleSelectEnumSchema|UntitledSingleSelectEnumSchema
 * @phpstan-type MultiSingleEnumSchema TitledMultiSelectEnumSchema|UntitledMultiSelectEnumSchema
 * @phpstan-type EnumSchema SingleEnumSchema|MultiSingleEnumSchema
 *
 * @template TSchema of PrimitiveSchema|EnumSchema = PrimitiveSchema|EnumSchema
 */
readonly class ElicitRequestFormParams implements JsonSerializable
{
    /**
     * @param RootObjectSchema<TSchema> $requestedSchema
     */
    public function __construct(
        public string $message,
        public RootObjectSchema $requestedSchema,
        public ?ElicitMode $mode = null,
    ) {}

    public function jsonSerialize(): stdClass
    {
        $object = (object) [
            'message' => $this->message,
            'requestedSchema' => $this->requestedSchema->jsonSerialize(),
        ];

        if ($this->mode) {
            $object->mode = $this->mode->value;
        }

        return $object;
    }
}
