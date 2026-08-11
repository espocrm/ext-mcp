<?php
/**LICENSE**/

namespace Espo\Modules\Mcp\Tools\Mcp\Tool;

use Espo\Modules\Mcp\Entities\Endpoint;
use Espo\Modules\Mcp\Entities\Feature;
use Espo\Modules\Mcp\Tools\Feature\DataFactory;
use Espo\Modules\Mcp\Tools\Feature\Exceptions\BadFeatureData;
use Espo\Modules\Mcp\Tools\Feature\Exceptions\NoUserAccess;
use Espo\Modules\Mcp\Tools\Feature\Exceptions\UnsupportedFeatureValue;
use Espo\Modules\Mcp\Tools\Feature\Exceptions\UnsupportedType;
use Espo\Modules\Mcp\Tools\Feature\ToolDefinitionProviderFactory;
use Espo\Modules\Mcp\Tools\Mcp\Exceptions\InternalError;
use Espo\Modules\Mcp\Tools\Mcp\Exceptions\InvalidParamsError;
use Espo\Modules\Mcp\Tools\Mcp\Schema\Tool\Tool;

class ToolsProvider
{
    private const int NAME_MAX_LENGTH = 64;

    public function __construct(
        private Endpoint $endpoint,
        private DataFactory $dataFactory,
        private ToolDefinitionProviderFactory $toolSchemaProviderFactory,
    ) {}

    /**
     * @throws InternalError
     * @throws InvalidParamsError
     */
    public function get(string $name): Tool
    {
        foreach ($this->getAll() as $tool) {
            if ($tool->name !== $name) {
                continue;
            }

            return $tool;
        }

        throw new InvalidParamsError("Tool `$name` not found.");
    }

    /**
     * @todo Cache. For user and endpoint.
     *
     * @return Tool[]
     * @throws InternalError
     */
    public function getAll(): array
    {
        $tools = [];

        foreach ($this->endpoint->getFeatures() as $feature) {
            $tool = $this->getForFeature($feature);

            if (!$tool) {
                continue;
            }

            $tools[] = $tool;
        }

        return $tools;
    }

    /**
     * @throws InternalError
     */
    private function getForFeature(Feature $feature): ?Tool
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

        try {
            $tool = $provider->get($data);
        } catch (UnsupportedFeatureValue $e) {
            throw new InternalError("Unsupported feature value.", previous: $e);
        } catch (NoUserAccess) {
            return null;
        }

        if (strlen($tool->name) > self::NAME_MAX_LENGTH) {
            throw new InternalError("Tool `$tool->name` name length should be not longer than 64 characters.");
        }

        return $tool;
    }
}
