<?php
/**LICENSE**/

namespace Espo\Modules\Mcp\Tools\Feature;

use Espo\Core\Utils\Metadata;
use Espo\Modules\Mcp\Entities\Feature;
use Espo\Modules\Mcp\Tools\Feature\Exceptions\BadFeatureData;
use Espo\Modules\Mcp\Tools\Feature\Exceptions\UnsupportedType;
use InvalidArgumentException;
use UnexpectedValueException;

class DataFactory
{
    public function __construct(
        private Metadata $metadata,
    ) {}

    /**
     * @throws UnsupportedType
     * @throws BadFeatureData
     */
    public function createForFeature(Feature $feature): Data
    {
        try {
            $type = $feature->getType();
        } catch (UnexpectedValueException $e) {
            throw new BadFeatureData("No type.", previous: $e);
        }

        try {
            $rawData = $feature->getRawData();
        } catch (UnexpectedValueException $e) {
            throw new BadFeatureData("No data.", previous: $e);
        }

        /** @var ?class-string<Data> $className */
        $className = $this->metadata->get("app.mcpFeatures.$type.record.dataClassName");

        if (!$className) {
            throw new UnsupportedType("Unsupported type '$type'.");
        }

        try {
            return $className::fromRaw($rawData);
        } catch (InvalidArgumentException $e) {
            $id = $feature->hasId() ? $feature->getId() : '?';

            throw new BadFeatureData("Bad data in feature record '$id'.", previous: $e);
        }
    }
}
