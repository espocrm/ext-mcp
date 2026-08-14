<?php
/**LICENSE**/

namespace Espo\Modules\Mcp\Tools\Feature\Read;

use Espo\Modules\Mcp\Tools\Feature\Data;
use Espo\Modules\Mcp\Tools\Feature\DataUtil;
use Espo\Modules\Mcp\Tools\Feature\Find\FindData\Field;
use InvalidArgumentException;
use stdClass;

readonly class ReadData implements Data
{
    public const string TYPE = 'Read';

    /**
     * @param Field[] $selectFields
     */
    public function __construct(
        public string $entityType,
        public array $selectFields,
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
            'selectFields' => array_map(fn ($it) => (object) get_object_vars($it), $this->selectFields),
        ];
    }

    public static function fromRaw(stdClass $raw): Data
    {
        $entityType = $raw->entityType ?? null;
        $selectFields = $raw->selectFields ?? null;

        if (!is_string($entityType)) {
            throw new InvalidArgumentException("No 'entityType'.");
        }

        DataUtil::assertArrayOfFields($selectFields, 'selectFields', true);

        return new ReadData(
            entityType: $entityType,
            selectFields: array_map(function ($it) {
                return new Field(
                    name: $it->name,
                    description: $it->description ?? null,
                );
            }, $selectFields),
        );
    }
}
