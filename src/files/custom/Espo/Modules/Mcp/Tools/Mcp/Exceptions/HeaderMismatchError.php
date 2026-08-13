<?php
/**LICENSE**/

namespace Espo\Modules\Mcp\Tools\Mcp\Exceptions;

class HeaderMismatchError extends Error
{
    protected int $rpcCode = -32020;

    protected int $httpCode = 400;
}
