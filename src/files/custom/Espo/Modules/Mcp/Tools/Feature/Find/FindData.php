<?php
/**LICENSE**/

namespace Espo\Modules\Mcp\Tools\Feature\Find;

use Espo\Modules\Mcp\Tools\Feature\Data;
use Espo\Modules\Mcp\Tools\Feature\DataUtil;
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
        return self::TYPE . '.' . $this->entityType;
    }

    /**
     * @throws InvalidArgumentException
     */
    public static function fromRaw(stdClass $raw): self
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

        DataUtil::assertArrayOfFields($selectFields, 'selectFields', true);
        DataUtil::assertArrayOfFields($filterFields, 'filterFields');

        DataUtil::assertArrayOfStrings($primaryFilters, 'primaryFilters');
        DataUtil::assertArrayOfStrings($boolFilters, 'boolFilters');

        return new self(
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
            'selectFields' => array_map(fn ($it) => (object) get_object_vars($it), $this->selectFields),
            'primaryFilters' => $this->primaryFilters,
            'boolFilters' => $this->boolFilters,
            'filterFields' => array_map(fn ($it) => (object) get_object_vars($it), $this->filterFields),
        ];
    }
}
