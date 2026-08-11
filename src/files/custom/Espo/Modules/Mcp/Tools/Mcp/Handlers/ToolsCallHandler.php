<?php
/**LICENSE**/

namespace Espo\Modules\Mcp\Tools\Mcp\Handlers;

use Espo\Core\Api\Request;
use Espo\Core\Api\Response;
use Espo\Core\Api\ResponseComposer;
use Espo\Modules\Mcp\Tools\Mcp\Exceptions\InvalidRequestError;
use Espo\Modules\Mcp\Tools\Mcp\Handler;
use Espo\Modules\Mcp\Tools\Mcp\RequestIdFetcher;
use Espo\Modules\Mcp\Tools\Mcp\Schema\GenericResponse;
use Espo\Modules\Mcp\Tools\Mcp\Schema\Tool\CallToolRequestParams;
use Espo\Modules\Mcp\Tools\Mcp\ToolsCall\ToolsCallGeneralProcessor;
use InvalidArgumentException;
use stdClass;

class ToolsCallHandler implements Handler
{
    public function __construct(
        private RequestIdFetcher $requestIdFetcher,
        private ToolsCallGeneralProcessor $generalProcessor,
    ) {}

    public function handle(Request $request): Response
    {
        $params = $this->fetchParams($request);

        $response = new GenericResponse(
            id: $this->requestIdFetcher->fetch($request),
            result: $this->generalProcessor->process($params),
        );

        return ResponseComposer::json($response->jsonSerialize());
    }

    /**
     * @throws InvalidRequestError
     */
    private function fetchParams(Request $request): CallToolRequestParams
    {
        $paramsRaw = $request->getParsedBody()->params ?? null;

        if (!$paramsRaw instanceof stdClass) {
            throw new InvalidRequestError();
        }

        try {
            $params = CallToolRequestParams::fromRaw($paramsRaw);
        } catch (InvalidArgumentException) {
            throw new InvalidRequestError("Bad params.");
        }

        return $params;
    }
}
