<?php
/**LICENSE**/

namespace Espo\Modules\Mcp\Tools\Mcp\Exceptions;

class InvalidParamsError extends Error
{
    protected int $rpcCode = -32602;

    public static function create(string $message = '', mixed $data = null): self
    {
        $object = new self($message);
        $object->data = $data;

        return $object;
    }
}
