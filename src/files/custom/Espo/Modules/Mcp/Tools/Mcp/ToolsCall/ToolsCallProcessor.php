<?php
/**LICENSE**/

namespace Espo\Modules\Mcp\Tools\Mcp\ToolsCall;

use Espo\Modules\Mcp\Tools\Mcp\JsonSchemaValidator\Validator;
use Espo\Modules\Mcp\Tools\Mcp\Exceptions\InternalError;
use Espo\Modules\Mcp\Tools\Mcp\Exceptions\InvalidParamsError;
use Espo\Modules\Mcp\Tools\Mcp\Schema\Tool\CallToolRequestParams;
use Espo\Modules\Mcp\Tools\Mcp\Schema\Tool\CallToolResult;
use Espo\Modules\Mcp\Tools\Mcp\Tool\ToolsProvider;

class ToolsCallProcessor
{
    public function __construct(
        private ToolsProvider $toolsProvider,
        private Validator $jsonSchemaValidator,
    ) {}

    /**
     * @throws InternalError
     * @throws InvalidParamsError
     */
    public function process(CallToolRequestParams $params): CallToolResult
    {
        $tool = $this->toolsProvider->get($params->name);

        $this->jsonSchemaValidator->assert($tool->inputSchema, $params->arguments);
    }
}
