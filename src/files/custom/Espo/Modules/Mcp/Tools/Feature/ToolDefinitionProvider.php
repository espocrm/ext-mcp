<?php
/**LICENSE**/

namespace Espo\Modules\Mcp\Tools\Feature;

use Espo\Modules\Mcp\Tools\Mcp\Schema\Tool\Tool;

/**
 * @template TData of Data
 */
interface ToolDefinitionProvider
{
    /**
     * @param TData $data
     */
    public function get(Data $data): Tool;
}
