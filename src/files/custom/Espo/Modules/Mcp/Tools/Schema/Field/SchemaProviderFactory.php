<?php
/**LICENSE**/

namespace Espo\Modules\Mcp\Tools\Schema\Field;

use Espo\Core\InjectableFactory;
use Espo\Core\Utils\Metadata;
use Espo\Modules\Mcp\Tools\Feature\Exceptions\UnsupportedFeatureValue;
use Espo\Modules\Mcp\Tools\Mcp\BindingProvider;
use Espo\ORM\Defs;
use Espo\ORM\Name\Attribute;

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
        $type = $this->getFieldType($entityType, $field);

        /** @var ?class-string<SchemaProvider> $className */
        $className =
            $this->metadata->get("entityDefs.$entityType.fields.$field.mcpFieldSchemaProviderClassName") ??
            $this->metadata->get("app.mcpSchema.fieldTypes.$type.schemaProviderClassName");

        if (!$className) {
            throw new UnsupportedFeatureValue("Unsupported field type '$type'. Field '$entityType.$field'.");
        }

        return $this->injectableFactory->createWithBinding($className, $this->bindingProvider->get());
    }

    /**
     * @throws UnsupportedFeatureValue
     */
    private function getFieldType(string $entityType, string $field): string
    {
        $fieldDefs = $this->defs
            ->tryGetEntity($entityType)
            ?->tryGetField($field);

        if ($field === Attribute::ID) {
            return $fieldDefs?->getType() ?? Attribute::ID;
        }

        if (!$fieldDefs) {
            throw new UnsupportedFeatureValue("No field '$entityType.$field'.");
        }

        return $fieldDefs->getType();
    }
}
