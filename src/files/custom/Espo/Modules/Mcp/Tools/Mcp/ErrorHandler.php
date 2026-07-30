<?php
/**LICENSE**/

namespace Espo\Modules\Mcp\Tools\Mcp;

use Espo\Core\Api\Request;
use Espo\Core\Api\Response;
use Espo\Core\Api\ResponseComposer;
use Espo\Core\Utils\Log;

class ErrorHandler
{
    public function __construct(
        private Log $log,
    ) {}

    public function handle(Request $request, Exceptions\Error $exception): Response
    {
        $this->log($exception);

        $error = [
            'code' => $exception->getRpcCode(),
            'message' => $exception->getMessage(),
        ];

        if ($exception->getData() !== null) {
            $error['data'] = $exception->getData();
        }

        $response = ResponseComposer::json([
            'jsonrpc' => '2.0',
            'id' => $request->getParsedBody()->id ?? null,
            'error' => $error,
        ]);

        $response->setStatus($exception->getHttpCode());

        return $response;
    }

    private function log(Exceptions\Error $exception): void
    {
        $code = $exception->getRpcCode();

        $this->log->warning("MCP: {$exception->getMessage()}; $code", ['exception' => $exception]);
    }
}
