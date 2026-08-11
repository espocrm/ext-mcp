<?php
/**LICENSE**/

namespace Espo\Modules\Mcp\Tools\Mcp\ToolsCall;

use Espo\Modules\Mcp\Tools\Mcp\JsonSchemaValidator\Validator;
use Espo\Modules\Mcp\Tools\Mcp\Exceptions\InternalError;
use Espo\Modules\Mcp\Tools\Mcp\Exceptions\InvalidParamsError;
use Espo\Modules\Mcp\Tools\Mcp\Schema\Tool\CallToolRequestParams;
use Espo\Modules\Mcp\Tools\Mcp\Schema\Tool\CallToolResult;
use Espo\Modules\Mcp\Tools\Mcp\Tool\ToolProvider;

class ToolsCallGeneralProcessor
{
    public function __construct(
        private ToolProvider $toolProvider,
        private Validator $jsonSchemaValidator,
        private ToolProcessorFactory $processorFactory,
    ) {}

    /**
     * @throws InternalError
     * @throws InvalidParamsError
     */
    public function process(CallToolRequestParams $params): CallToolResult
    {
        $this->validate($params);

        $processor = $this->processorFactory->create($params->name);

        return $processor->process($params);
    }

    /**
     * @throws InternalError
     * @throws InvalidParamsError
     */
    private function validate(CallToolRequestParams $params): void
    {
        $tool = $this->toolProvider->get($params->name);

        $this->jsonSchemaValidator->assert($tool->inputSchema, $params->arguments);
    }
}
