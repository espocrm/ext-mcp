<?php
/**LICENSE**/

namespace Espo\Modules\Mcp\Tools\Mcp\Exceptions;

class InvalidParamsError extends Error
{
    protected int $rpcCode = -32602;
}
