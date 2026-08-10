<?php
/**LICENSE**/

namespace Espo\Modules\Mcp\Tools\JsonSchema;

use Espo\Modules\Mcp\Tools\JsonSchema\Type\Type;
use Espo\Modules\Mcp\Tools\Schema\Field\Traits\CommonTrait;
use LogicException;
use stdClass;
use UnexpectedValueException;

class UnionTypeSchema implements Schema
{
    use CommonTrait;

    /**
     * @param Type[] $schemas
     */
    public function __construct(
        private array $schemas,
        private ?string $title = null,
        private ?string $description = null,
    ) {
        // Validates.
        $this->jsonSerialize();
    }

    public function jsonSerialize(): stdClass
    {
        $types = [];

        $merged = [];

        foreach ($this->schemas as $schema) {
            $item = $schema->jsonSerialize();

            $type = $item->type ?? null;

            if (!is_string($type)) {
                throw new LogicException();
            }

            if (in_array($type, $types)) {
                throw new UnexpectedValueException("Cannot use the same type in union type.");
            }

            $itemAssoc = get_object_vars($item);
            unset($itemAssoc['type']);

            foreach ($itemAssoc as $k => $v) {
                if (array_key_exists($k, $merged)) {
                    throw new UnexpectedValueException("Cannot have same attributes in schemas in union type.");
                }

                $merged[$k] = $v;
            }

            $types[] = $type;
        }

        $object = (object) [
            'type' => $types,
            ...$merged,
        ];

        if ($this->title !== null) {
            $object->title = $this->title;
        }

        if ($this->description !== null) {
            $object->description = $this->description;
        }

        return $object;
    }
}
