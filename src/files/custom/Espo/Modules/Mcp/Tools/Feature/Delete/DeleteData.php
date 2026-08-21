<?php
/**LICENSE**/

namespace Espo\Modules\Mcp\Tools\Feature\Delete;

use Espo\Modules\Mcp\Tools\Feature\Data;
use InvalidArgumentException;
use stdClass;

class DeleteData implements Data
{
    public const string TYPE = 'Delete';

    public function __construct(
        public string $entityType,
    ) {}

    public function composeName(): string
    {
        return $this->getKey();
    }

    public function getKey(): string
    {
        return self::TYPE . '.' . $this->entityType;
    }

    public function jsonSerialize(): stdClass
    {
        return (object) [
            'entityType' => $this->entityType,
        ];
    }

    public static function fromRaw(stdClass $raw): self
    {
        $entityType = $raw->entityType ?? null;

        if (!is_string($entityType)) {
            throw new InvalidArgumentException("No 'entityType'.");
        }

        return new self(
            entityType: $entityType,
        );
    }
}
