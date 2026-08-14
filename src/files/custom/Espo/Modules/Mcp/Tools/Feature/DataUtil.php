<?php
/**LICENSE**/

namespace Espo\Modules\Mcp\Tools\Feature;

use InvalidArgumentException;
use stdClass;

class DataUtil
{
    /**
     * @phpstan-assert (object{name: string, description?: string|null} & stdClass)[] $value
     */
    public static function assertArrayOfFields(mixed $value, string $name, bool $hasDescription = false): void
    {
        if (!is_array($value)) {
            throw new InvalidArgumentException("No '$name'.");
        }

        foreach ($value as $it) {
            if (!$it instanceof stdClass) {
                throw new InvalidArgumentException("Bad '$name'.");
            }

            if (!property_exists($it, 'name')) {
                throw new InvalidArgumentException("Bad '$name'.");
            }

            if (!is_string($it->name)) {
                throw new InvalidArgumentException("Bad '$name'.");
            }

            if ($hasDescription) {
                if (
                    property_exists($it, 'description') &&
                    !is_string($it->description) &&
                    !is_null($it->description)
                ) {
                    throw new InvalidArgumentException("Bad '$name'.");
                }
            }
        }
    }

    /**
     * @phpstan-assert string[] $value
     */
    public static function assertArrayOfStrings(mixed $value, string $name): void
    {
        if (!is_array($value)) {
            throw new InvalidArgumentException("No '$name'.");
        }

        foreach ($value as $it) {
            if (!is_string($it)) {
                throw new InvalidArgumentException("Bad '$name'.");
            }
        }
    }
}
