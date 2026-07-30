<?php
/**LICENSE**/

namespace Espo\Modules\Mcp\Tools\Mcp\Exceptions;

class InternalError extends Error
{
    protected int $rpcCode = -32603;
}
