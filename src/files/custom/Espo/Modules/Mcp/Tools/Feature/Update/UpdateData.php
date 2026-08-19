<?php
/**LICENSE**/

namespace Espo\Modules\Mcp\Tools\Feature\Update;

use Espo\Modules\Mcp\Tools\Feature\Data;
use Espo\Modules\Mcp\Tools\Feature\DataUtil;
use Espo\Modules\Mcp\Tools\Feature\Find\FindData\Field;
use InvalidArgumentException;
use stdClass;

class UpdateData implements Data
{
    public const string TYPE = 'Update';

    /**
     * @param Field[] $writeFields
     */
    public function __construct(
        public string $entityType,
        public array $writeFields,
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
            'writeFields' => array_map(fn ($it) => (object) get_object_vars($it), $this->writeFields),
        ];
    }

    public static function fromRaw(stdClass $raw): self
    {
        $entityType = $raw->entityType ?? null;
        $writeFields = $raw->writeFields ?? null;

        if (!is_string($entityType)) {
            throw new InvalidArgumentException("No 'entityType'.");
        }

        DataUtil::assertArrayOfFields($writeFields, 'writeFields', true);

        return new self(
            entityType: $entityType,
            writeFields: array_map(function ($it) {
                return new Field(
                    name: $it->name,
                    description: $it->description ?? null,
                );
            }, $writeFields),
        );
    }
}
