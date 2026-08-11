<?php
/**LICENSE**/

namespace Espo\Modules\Mcp\Tools\Mcp\Exceptions;

class UnsupportedProtocolVersionError extends Error
{
    protected int $rpcCode = -32022;

    protected int $httpCode = 400;

    /**
     * @param string[] $supported
     */
    public static function create(array $supported, string $requested, string $message): self
    {
        $object = new self(message: $message);

        $object->data = [
            'supported' => $supported,
            'requested' => $requested,
        ];

        return $object;
    }
}
