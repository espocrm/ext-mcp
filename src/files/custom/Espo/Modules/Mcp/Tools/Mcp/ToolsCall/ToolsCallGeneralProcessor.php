<?php
/**LICENSE**/

namespace Espo\Modules\Mcp\Tools\Mcp\ToolsCall;

use Espo\Modules\Mcp\Entities\Feature;
use Espo\Modules\Mcp\Tools\Mcp\JsonSchemaValidator\Validator;
use Espo\Modules\Mcp\Tools\Mcp\Exceptions\InternalError;
use Espo\Modules\Mcp\Tools\Mcp\Exceptions\InvalidParamsError;
use Espo\Modules\Mcp\Tools\Mcp\Schema\Tool\CallToolRequestParams;
use Espo\Modules\Mcp\Tools\Mcp\Schema\Tool\CallToolResult;
use Espo\Modules\Mcp\Tools\Mcp\Tool\ToolEnvelope;
use Espo\Modules\Mcp\Tools\Mcp\Tool\ToolProvider;
use Espo\ORM\EntityManager;

class ToolsCallGeneralProcessor
{
    public function __construct(
        private ToolProvider $toolProvider,
        private Validator $jsonSchemaValidator,
        private ToolProcessorFactory $processorFactory,
        private EntityManager $entityManager,
    ) {}

    /**
     * @throws InternalError
     * @throws InvalidParamsError
     */
    public function process(CallToolRequestParams $params): CallToolResult
    {
        $toolEnvelope = $this->toolProvider->get($params->name);

        $this->jsonSchemaValidator->assert($toolEnvelope->tool->inputSchema, $params->arguments);

        $feature = $this->getFeature($toolEnvelope->featureId, $params->name);

        $processor = $this->processorFactory->create($params->name);

        return $processor->process($params, $feature);
    }

    /**
     * @throws InternalError
     */
    private function getFeature(string $id, string $name): Feature
    {
        $feature = $this->entityManager->getRDBRepositoryByClass(Feature::class)->getById($id);

        if (!$feature) {
            throw new InternalError("Feature `$id` for tool `$name` not found.");
        }

        return $feature;
    }
}
