<?php
/**LICENSE**/

namespace Espo\Modules\Mcp\Tools\Feature\Create;

use Espo\Core\Exceptions\BadRequest;
use Espo\Core\Exceptions\Conflict;
use Espo\Core\Exceptions\Forbidden;
use Espo\Core\Record\CreateParams;
use Espo\Core\Record\ServiceFactory;
use Espo\Entities\User;
use Espo\Modules\Mcp\Tools\Feature\Data;
use Espo\Modules\Mcp\Tools\Feature\Update\UpdateToolProcessor;
use Espo\Modules\Mcp\Tools\Feature\Utils\ExceptionUtil;
use Espo\Modules\Mcp\Tools\Feature\Utils\ResourceLinkPreparator;
use Espo\Modules\Mcp\Tools\Mcp\Exceptions\InternalError;
use Espo\Modules\Mcp\Tools\Mcp\Schema\Elicitation\ElicitAction;
use Espo\Modules\Mcp\Tools\Mcp\Schema\General\RootSchema;
use Espo\Modules\Mcp\Tools\Mcp\Schema\Tool\CallToolRequestParams;
use Espo\Modules\Mcp\Tools\Mcp\Schema\Tool\CallToolResult;
use Espo\Modules\Mcp\Tools\Mcp\ToolsCall\ToolProcessor;
use Exception;
use RuntimeException;
use stdClass;

/**
 * @implements ToolProcessor<CreateData>
 */
class CreateToolProcessor implements ToolProcessor
{
    private const string KEY_CONFIRM_DUPLICATE = UpdateToolProcessor::KEY_CONFIRM_DUPLICATE;

    public function __construct(
        private ServiceFactory $serviceFactory,
        private User $user,
        private ExceptionUtil $exceptionUtil,
        private ResourceLinkPreparator $resourceLinkPreparator,
    ) {}

    public function process(CallToolRequestParams $params, Data $data, ?RootSchema $outputSchema): CallToolResult
    {
        $entityType = $data->entityType;

        try {
            $service = $this->serviceFactory->createForUser($entityType, $this->user);
        } catch (Exception $e) {
            throw new InternalError("Could not create record service for `$entityType`.", previous: $e);
        }

        $input = $this->fetchInput($params);
        $createParams = $this->prepareCreateParams($params);

        try {
            $createResult = $service->create($input, $createParams);
        } catch (BadRequest $e) {
            return $this->exceptionUtil->prepareWriteBadRequestResult($e);
        } catch (Forbidden $e) {
            return $this->exceptionUtil->prepareWriteForbiddenResult($e);
        } catch (Conflict $e) {
            return $this->exceptionUtil->prepareWriteConflictResult($e);
        }

        return new CallToolResult(
            structuredContent: (object) [
                'record' => (object) [
                    'id' => $createResult->getEntity()->getId(),
                ],
            ],
            content: [
                $this->resourceLinkPreparator->prepare($createResult->getEntity()),
            ],
        );
    }

    private function prepareCreateParams(CallToolRequestParams $params): CreateParams
    {
        $skipDuplicateCheck = false;

        if ($params->arguments->skipDuplicateCheck ?? false) {
            $skipDuplicateCheck = true;
        }

        $confirmDuplicate = $params->inputResponses[self::KEY_CONFIRM_DUPLICATE] ?? null;

        if ($confirmDuplicate && $confirmDuplicate->action === ElicitAction::Accept) {
            $skipDuplicateCheck = true;
        }

        return (new CreateParams())->withSkipDuplicateCheck($skipDuplicateCheck);
    }

    private function fetchInput(CallToolRequestParams $params): stdClass
    {
        $input = $params->arguments->record ?? (object) [];

        if (!$input instanceof stdClass) {
            throw new RuntimeException("Bad input.");
        }

        return $input;
    }
}
