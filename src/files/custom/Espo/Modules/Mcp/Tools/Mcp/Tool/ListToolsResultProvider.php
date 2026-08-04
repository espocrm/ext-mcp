<?php
/**LICENSE**/

namespace Espo\Modules\Mcp\Tools\Mcp\Tool;

use Espo\Modules\Mcp\Entities\Endpoint;
use Espo\Modules\Mcp\Tools\Mcp\Schema\Tool\ListToolsResult;

class ListToolsResultProvider
{
    public function __construct(
        private Endpoint $endpoint,
    ) {}

    public function get(): ListToolsResult
    {}
}
