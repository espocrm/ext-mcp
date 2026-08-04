<?php
/**LICENSE**/

namespace Espo\Modules\Mcp\Tools\Mcp\Tool;

use Espo\Entities\User;
use Espo\Modules\Mcp\Entities\Endpoint;
use Espo\Modules\Mcp\Tools\Mcp\Schema\Tool\Tool;

class ToolsProvider
{
    public function __construct(
        private Endpoint $endpoint,
        private User $user,
    ) {}

    /**
     * @return Tool[]
     */
    public function get(): array
    {

    }
}
