<?php
/**LICENSE**/

namespace Espo\Modules\Mcp\Tools\Mcp\Exceptions;

class ParseError extends Error
{
    protected int $rpcCode = -32700;
}
