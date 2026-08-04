<?php
/**LICENSE**/

namespace Espo\Modules\Mcp\Tools\Mcp\Tool;

use Espo\Modules\Mcp\Entities\Endpoint;
use Espo\Modules\Mcp\Entities\Feature;
use Espo\Modules\Mcp\Tools\Feature\DataFactory;
use Espo\Modules\Mcp\Tools\Feature\Exceptions\BadFeatureData;
use Espo\Modules\Mcp\Tools\Feature\Exceptions\UnsupportedType;
use Espo\Modules\Mcp\Tools\Feature\ToolDefinitionProviderFactory;
use Espo\Modules\Mcp\Tools\Mcp\Exceptions\InternalError;
use Espo\Modules\Mcp\Tools\Mcp\Schema\Tool\Tool;

class ToolsProvider
{
    public function __construct(
        private Endpoint $endpoint,
        private DataFactory $dataFactory,
        private ToolDefinitionProviderFactory $toolSchemaProviderFactory,
    ) {}

    /**
     * @todo Cache.
     *
     * @return Tool[]
     * @throws InternalError
     */
    public function get(): array
    {
        $tools = [];

        foreach ($this->endpoint->getFeatures() as $feature) {
            $tools[] = $this->getOne($feature);
        }

        return $tools;
    }

    /**
     * @throws InternalError
     */
    private function getOne(Feature $feature): Tool
    {
        try {
            $data = $this->dataFactory->createForFeature($feature);
        } catch (BadFeatureData|UnsupportedType $e) {
            throw new InternalError("Could not create data object.", previous: $e);
        }

        try {
            $provider = $this->toolSchemaProviderFactory->create($feature->getType());
        } catch (UnsupportedType $e) {
            throw new InternalError("Could not create tool schema provider.", previous: $e);
        }

        return $provider->get($data);
    }
}
