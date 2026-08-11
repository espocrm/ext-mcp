<?php
/**LICENSE**/

namespace Espo\Modules\Mcp\Tools\Mcp\ToolsCall;

use Espo\Core\InjectableFactory;
use Espo\Core\Utils\Metadata;
use Espo\Modules\Mcp\Tools\Mcp\BindingProvider;
use Espo\Modules\Mcp\Tools\Mcp\Exceptions\InvalidParamsError;

class ToolProcessorFactory
{
    public function __construct(
        private BindingProvider $bindingProvider,
        private Metadata $metadata,
        private InjectableFactory $injectableFactory,
    ) {}

    /**
     * @throws InvalidParamsError
     */
    public function create(string $name): ToolProcessor
    {
        $feature = explode('.', $name)[0];

        /** @var ?class-string<ToolProcessor> $className */
        $className = $this->metadata->get("app.mcpFeatures.$feature.tool.processorClassName");

        if (!$className) {
            throw new InvalidParamsError("Tool `$name` not found. No processor for `$feature` feature.");
        }

        return $this->injectableFactory->createWithBinding($className, $this->bindingProvider->get());
    }
}
