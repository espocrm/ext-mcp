<?php
/**LICENSE**/

namespace Espo\Modules\Mcp\Tools\Mcp;

use Espo\Core\Api\Request;
use Espo\Core\Api\Response;
use Espo\Modules\Mcp\Tools\Mcp\Exceptions\Error;

interface Handler
{
    /**
     * @throws Error
     */
    public function handle(Request $request): Response;
}
