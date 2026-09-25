<?php
/**LICENSE**/

namespace Espo\Modules\Mcp\Tools\Feature\RecordStream;

use Espo\Modules\Mcp\Tools\Feature\Data;
use InvalidArgumentException;
use stdClass;

class RecordStreamData implements Data
{
    public const string TYPE = 'RecordStream';

    /**
     * @param string[] $entityTypes
     */
    public function __construct(
        public array $entityTypes,
    ) {}

    public function composeName(): string
    {
        return $this->getKey();
    }

    public function jsonSerialize(): stdClass
    {
        return (object) [
            'entityTypes' => $this->entityTypes,
        ];
    }

    public static function fromRaw(stdClass $raw): Data
    {
        $entityTypes = $raw->entityTypes ?? null;

        if (!is_array($entityTypes)) {
            throw new InvalidArgumentException("No or bad 'entityTypes'.");
        }

        foreach ($entityTypes as $it) {
            if (!is_string($it)) {
                throw new InvalidArgumentException("Bad 'entityTypes' item");
            }
        }

        sort($entityTypes);

        return new self(
            entityTypes: $entityTypes,
        );
    }

    public function getKey(): string
    {
        return self::TYPE;
    }
}
