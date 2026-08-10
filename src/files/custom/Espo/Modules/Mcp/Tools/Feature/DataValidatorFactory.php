<?php
/**LICENSE**/

namespace Espo\Modules\Mcp\Tools\Feature;

use Espo\Core\InjectableFactory;
use Espo\Core\Utils\Metadata;
use Espo\Modules\Mcp\Tools\Feature\Exceptions\UnsupportedType;

class DataValidatorFactory
{
    public function __construct(
        private Metadata $metadata,
        private InjectableFactory $injectableFactory,
    ) {}

    /**
     * @return DataValidator<Data>
     * @throws UnsupportedType
     */
    public function create(string $type): DataValidator
    {
        /** @var ?class-string<DataValidator<Data>> $className */
        $className = $this->metadata->get("app.mcpFeatures.$type.record.dataValidatorClassName");

        if (!$className) {
            throw new UnsupportedType("Unsupported type '$type'.");
        }

        return $this->injectableFactory->create($className);
    }
}
