<?php
/**LICENSE**/

namespace Espo\Modules\Mcp\Tools\Mcp\Exceptions;

class InvalidRequestError extends Error
{
    protected int $rpcCode = -32600;
}
