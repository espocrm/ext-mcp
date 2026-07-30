<?php
/**LICENSE**/

namespace Espo\Modules\Mcp\Tools\Mcp\Exceptions;

use Exception;

abstract class Error extends Exception
{
    protected int $rpcCode = 0;

    protected int $httpCode = 200;

    protected mixed $data = null;

    public function getRpcCode(): int
    {
        return $this->rpcCode;
    }

    public function getHttpCode(): int
    {
        return $this->httpCode;
    }

    public function getData(): mixed
    {
        return $this->data;
    }
}
