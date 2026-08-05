<?php
/**LICENSE**/

namespace Espo\Modules\Mcp\Tools\Feature;

use Espo\Modules\Mcp\Tools\Feature\Exceptions\NoUserAccess;
use Espo\Modules\Mcp\Tools\Feature\Exceptions\UnsupportedFeatureValue;
use Espo\Modules\Mcp\Tools\Mcp\Schema\Tool\Tool;

/**
 * @template TData of Data
 */
interface ToolDefinitionProvider
{
    /**
     * @param TData $data
     * @throws UnsupportedFeatureValue
     * @throws NoUserAccess
     */
    public function get(Data $data): Tool;
}
