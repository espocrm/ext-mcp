<?php
/**LICENSE**/

namespace Espo\Modules\Mcp\Tools\Feature;

use Espo\Core\InjectableFactory;
use Espo\Core\Utils\Metadata;
use Espo\Modules\Mcp\Tools\Feature\Exceptions\UnsupportedType;
use Espo\Modules\Mcp\Tools\Mcp\BindingProvider;

class ToolSchemaProviderFactory
{
    public function __construct(
        private Metadata $metadata,
        private InjectableFactory $injectableFactory,
        private BindingProvider $bindingProvider,
    ) {}

    /**
     * @return ToolSchemaProvider<Data>
     * @throws UnsupportedType
     */
    public function create(string $type): ToolSchemaProvider
    {
        /** @var ?class-string<ToolSchemaProvider<Data>> $className */
        $className = $this->metadata->get("app.mcpFeatures.$type.tool.schemaProviderClassName");

        if (!$className) {
            throw new UnsupportedType("Unsupported type '$type'.");
        }

        return $this->injectableFactory->createWithBinding($className, $this->bindingProvider->get());
    }
}
