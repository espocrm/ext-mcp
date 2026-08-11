<?php
/**LICENSE**/

namespace Espo\Modules\Mcp\Tools\Mcp\ToolsCall;

use Espo\Modules\Mcp\Entities\Feature;
use Espo\Modules\Mcp\Tools\Mcp\Exceptions\InternalError;
use Espo\Modules\Mcp\Tools\Mcp\Schema\Tool\CallToolRequestParams;
use Espo\Modules\Mcp\Tools\Mcp\Schema\Tool\CallToolResult;

/**
 * Endpoint, User, Acl are passed to the constructor,
 */
interface ToolProcessor
{
    /**
     * @throws InternalError
     */
    public function process(CallToolRequestParams $params, Feature $feature): CallToolResult;
}
