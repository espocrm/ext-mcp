<?php
/**LICENSE**/

namespace Espo\Modules\Mcp\Tools\Mcp\Exceptions;

class MethodNotFoundError extends Error
{
    protected int $rpcCode = -32601;
}
