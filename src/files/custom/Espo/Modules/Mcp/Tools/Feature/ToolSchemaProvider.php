<?php
/**LICENSE**/

namespace Espo\Modules\Mcp\Tools\Feature;

use Espo\Modules\Mcp\Tools\Mcp\Schema\General\ArbitrarySchema;
use Espo\Modules\Mcp\Tools\Mcp\Schema\General\ObjectSchema;

/**
 * @template TData of Data
 */
interface ToolSchemaProvider
{
    /**
     * @param TData $data
     */
    public function getInput(Data $data): ObjectSchema;

    /**
     * @param TData $data
     */
    public function getOutput(Data $data): ArbitrarySchema;
}
