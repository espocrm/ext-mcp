<?php
/**LICENSE**/

namespace Espo\Modules\Mcp\Tools\Schema\FieldFilter;

use Espo\Core\InjectableFactory;
use Espo\Core\Utils\Metadata;
use Espo\Modules\Mcp\Tools\Feature\Exceptions\UnsupportedFeatureValue;
use Espo\Modules\Mcp\Tools\Mcp\BindingProvider;
use Espo\ORM\Defs;

class SchemaProviderFactory
{
    public function __construct(
        private InjectableFactory $injectableFactory,
        private Metadata $metadata,
        private BindingProvider $bindingProvider,
        private Defs $defs,
    ) {}

    /**
     * @throws UnsupportedFeatureValue
     */
    public function create(string $entityType, string $field): SchemaProvider
    {
        $fieldDefs = $this->defs
            ->tryGetEntity($entityType)
            ?->tryGetField($field);

        if (!$fieldDefs) {
            throw new UnsupportedFeatureValue("No field '$entityType.$field'.");
        }

        $type = $fieldDefs->getType();

        /** @var ?class-string<SchemaProvider> $className */
        $className =
            $this->metadata->get("entityDefs.$entityType.fields.$field.mcpFilterSchemaProviderClassName") ??
            $this->metadata->get("app.mcpSchema.fieldTypes.$type.filterSchemaProviderClassName");

        if (!$className) {
            throw new UnsupportedFeatureValue("Unsupported field type '$type'. Field filter '$entityType.$field'.");
        }

        return $this->injectableFactory->createWithBinding($className, $this->bindingProvider->get());
    }
}
