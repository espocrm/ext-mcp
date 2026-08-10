<?php
/**LICENSE**/

namespace Espo\Modules\Mcp\Tools\Feature\Find;

use Espo\Modules\Mcp\Tools\Feature\Data;
use Espo\Modules\Mcp\Tools\Feature\Find\FindData\Field;
use InvalidArgumentException;
use stdClass;

readonly class FindData implements Data
{
    public const string TYPE = 'Find';

    /**
     * @param Field[] $selectFields
     * @param string[] $primaryFilters
     * @param string[] $boolFilters
     * @param Field[] $filterFields
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
        return $this->getKey();
    }

    public function getKey(): string
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

        self::assertArrayOfFields($selectFields, 'selectFields', true);
        self::assertArrayOfFields($filterFields, 'filterFields');

        self::assertArrayOfStrings($primaryFilters, 'primaryFilters');
        self::assertArrayOfStrings($boolFilters, 'boolFilters');

        return new FindData(
            entityType: $entityType,
            textFilter: $textFilter,
            selectFields: array_map(function ($it) {
                return new Field(
                    name: $it->name,
                    description: $it->description ?? null,
                );
            }, $selectFields),
            primaryFilters: $primaryFilters,
            boolFilters: $boolFilters,
            filterFields: array_map(function ($it) {
                return new Field(
                    name: $it->name,
                    description: $it->description ?? null,
                );
            }, $filterFields),
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
     * @phpstan-assert (object{name: string, description?: string|null} & stdClass)[] $value
     */
    private static function assertArrayOfFields(mixed $value, string $name, bool $hasDescription = false): void
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
