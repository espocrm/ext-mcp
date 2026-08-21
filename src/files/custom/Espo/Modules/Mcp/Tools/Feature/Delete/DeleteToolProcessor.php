<?php
/**LICENSE**/

namespace Espo\Modules\Mcp\Tools\Feature\Delete;

use Espo\Core\Exceptions\BadRequest;
use Espo\Core\Exceptions\Conflict;
use Espo\Core\Exceptions\Forbidden;
use Espo\Core\Exceptions\NotFound;
use Espo\Core\Record\ServiceFactory;
use Espo\Entities\User;
use Espo\Modules\Mcp\Tools\Feature\Data;
use Espo\Modules\Mcp\Tools\Feature\Utils\ExceptionUtil;
use Espo\Modules\Mcp\Tools\Mcp\Exceptions\InternalError;
use Espo\Modules\Mcp\Tools\Mcp\Schema\General\RootSchema;
use Espo\Modules\Mcp\Tools\Mcp\Schema\Tool\CallToolRequestParams;
use Espo\Modules\Mcp\Tools\Mcp\Schema\Tool\CallToolResult;
use Espo\Modules\Mcp\Tools\Mcp\ToolsCall\ToolProcessor;
use Exception;

/**
 * @implements ToolProcessor<DeleteData>
 */
class DeleteToolProcessor implements ToolProcessor
{
    public function __construct(
        private ServiceFactory $serviceFactory,
        private User $user,
        private ExceptionUtil $exceptionUtil,
    ) {}

    public function process(CallToolRequestParams $params, Data $data, ?RootSchema $outputSchema): CallToolResult
    {
        $entityType = $data->entityType;

        try {
            $service = $this->serviceFactory->createForUser($entityType, $this->user);
        } catch (Exception $e) {
            throw new InternalError("Could not create record service for `$entityType`.", previous: $e);
        }

        $id = $this->fetchId($params);

        try {
            $service->delete($id);
        } catch (BadRequest $e) {
            return $this->exceptionUtil->prepareWriteBadRequestResult($e);
        } catch (Forbidden $e) {
            return $this->exceptionUtil->prepareWriteForbiddenResult($e);
        } catch (Conflict $e) {
            return $this->exceptionUtil->prepareWriteConflictResult($e);
        } catch (NotFound) {
            return new CallToolResult(
                structuredContent: (object) [
                    'error' => (object) [
                        'code' => 404,
                        'message' => 'Record not found.',
                    ],
                ],
                isError: true,
            );
        }

        return new CallToolResult(
            structuredContent: (object) [
                'record' => (object) [
                    'id' => $id,
                ],
            ],
        );
    }

    /**
     * @throws InternalError
     */
    private function fetchId(CallToolRequestParams $params): string
    {
        $id = $params->arguments->id ?? null;

        if (!is_string($id) || !$id) {
            throw new InternalError("No or bad ID.");
        }

        return $id;
    }
}
