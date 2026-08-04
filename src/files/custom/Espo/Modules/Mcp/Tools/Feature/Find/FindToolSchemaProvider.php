<?php
/**LICENSE**/

namespace Espo\Modules\Mcp\Tools\Feature\Find;

use Espo\Modules\Mcp\Tools\Feature\Data;
use Espo\Modules\Mcp\Tools\Feature\ToolSchemaProvider;
use Espo\Modules\Mcp\Tools\Mcp\Schema\General\ArbitrarySchema;
use Espo\Modules\Mcp\Tools\Mcp\Schema\General\ObjectSchema;

/**
 * @implements ToolSchemaProvider<FindData>
 */
class FindToolSchemaProvider implements ToolSchemaProvider
{
    public function getInput(Data $data): ObjectSchema
    {
        // TODO: Implement getInput() method.
    }

    public function getOutput(Data $data): ArbitrarySchema
    {
        // TODO: Implement getOutput() method.
    }
}
