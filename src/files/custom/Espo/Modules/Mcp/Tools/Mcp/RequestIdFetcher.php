<?php
/**LICENSE**/

namespace Espo\Modules\Mcp\Tools\Mcp;

use Espo\Core\Api\Request;
use Espo\Modules\Mcp\Tools\Mcp\Exceptions\InvalidRequestError;

class RequestIdFetcher
{
    /**
     * @throws InvalidRequestError
     */
    public function fetch(Request $request): int|string
    {
        $id = $request->getParsedBody()->id ?? null;

        if ($id === null) {
            throw new InvalidRequestError("No request ID.");
        }

        if (!is_string($id) && !is_int($id)) {
            throw new InvalidRequestError("Bad request ID.");
        }

        return $id;
    }
}
