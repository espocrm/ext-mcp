<?php
/**LICENSE**/

namespace Espo\Modules\Mcp\Tools\Mcp;

use Espo\Core\Api\Request;
use Espo\Modules\Mcp\Tools\Mcp\Exceptions\Error;

interface Hook
{
    /**
     * @throws Error
     */
    public function process(Request $request): void;
}
