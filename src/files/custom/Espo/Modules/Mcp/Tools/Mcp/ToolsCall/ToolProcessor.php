<?php
/**LICENSE**/

namespace Espo\Modules\Mcp\Tools\Mcp\ToolsCall;

use Espo\Modules\Mcp\Tools\Feature\Data;
use Espo\Modules\Mcp\Tools\Mcp\Exceptions\InternalError;
use Espo\Modules\Mcp\Tools\Mcp\Schema\General\RootSchema;
use Espo\Modules\Mcp\Tools\Mcp\Schema\Tool\CallToolRequestParams;
use Espo\Modules\Mcp\Tools\Mcp\Schema\Tool\CallToolResult;

/**
 * Endpoint, User, Acl are passed to the constructor,
 *
 * @template TData of Data = Data
 */
interface ToolProcessor
{
    /**
     * @param TData $data
     * @throws InternalError
     */
    public function process(CallToolRequestParams $params, Data $data, ?RootSchema $outputSchema): CallToolResult;
}
