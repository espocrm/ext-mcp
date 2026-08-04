<?php
/**LICENSE**/

namespace Espo\Modules\Mcp\Tools\Feature\Find;

use Espo\Modules\Mcp\Tools\Feature\Data;
use InvalidArgumentException;
use stdClass;

readonly class FindData implements Data
{
    public const string TYPE = 'Find';

    /**
     * @param string[] $selectFields
     * @param string[] $primaryFilters
     * @param string[] $boolFilters
     * @param string[] $filterFields
     */
    public function __construct(
        public string $entityType,
        public bool $textFilter,
        public array $selectFields,
        public array $primaryFilters,
        public array $boolFilters,
        public array $filterFields,
    ) {}

    public function composeName(): string
    {
        return $this->entityType . '.' . self::TYPE;
    }

    /**
     * @throws InvalidArgumentException
     */
    public static function fromRaw(stdClass $raw): Data
    {
        $entityType = $raw->entityType ?? null;
        $textFilter = $raw->textFilter ?? null;
        $selectFields = $raw->selectFields ?? null;
        $primaryFilters = $raw->primaryFilters ?? null;
        $boolFilters = $raw->boolFilters ?? null;
        $filterFields = $raw->filterFields ?? null;

        if (!is_string($entityType)) {
            throw new InvalidArgumentException("No 'entityType'.");
        }

        if (!is_bool($textFilter)) {
            throw new InvalidArgumentException("No 'textFilter.");
        }

        self::assertArrayOfStrings($selectFields, 'selectFields');
        self::assertArrayOfStrings($primaryFilters, 'primaryFilters');
        self::assertArrayOfStrings($boolFilters, 'boolFilters');
        self::assertArrayOfStrings($filterFields, 'filterFields');

        return new FindData(
            entityType: $entityType,
            textFilter: $textFilter,
            selectFields: $selectFields,
            primaryFilters: $primaryFilters,
            boolFilters: $boolFilters,
            filterFields: $filterFields,
        );
    }

    public function jsonSerialize(): stdClass
    {
        return (object) [
            'entityType' => $this->entityType,
            'textFilter' => $this->textFilter,
            'selectFields' => $this->selectFields,
            'primaryFilters' => $this->primaryFilters,
            'boolFilters' => $this->boolFilters,
            'filterFields' => $this->filterFields,
        ];
    }

    /**
     * @phpstan-assert string[] $value
     */
    private static function assertArrayOfStrings(mixed $value, string $name): void
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
