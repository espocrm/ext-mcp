<?php
/**LICENSE**/

namespace Espo\Modules\Mcp\Tools\Mcp;

use Espo\Core\Api\Request;
use Espo\Core\Api\Response;
use Espo\Core\Api\ResponseComposer;
use Espo\Core\Utils\Log;
use Espo\Modules\Mcp\Tools\Mcp\Exceptions\InternalError;

class ErrorHandler
{
    private const string LOG_LEVEL_WARNING = 'warning';
    private const string LOG_LEVEL_INFO = 'info';

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
            'jsonrpc' => JsonRpc::VERSION_2_0,
            'id' => $request->getParsedBody()->id ?? null,
            'error' => $error,
        ]);

        $response->setStatus($exception->getHttpCode());

        return $response;
    }

    private function log(Exceptions\Error $exception): void
    {
        $code = $exception->getRpcCode();

        $level = self::LOG_LEVEL_INFO;

        if ($exception instanceof InternalError) {
            $level = self::LOG_LEVEL_WARNING;
        }

        $this->log->log($level, "MCP. {$exception->getMessage()}; $code", ['exception' => $exception]);
    }
}
