<?php
/**LICENSE**/

namespace Espo\Modules\Mcp\Tools\Feature\Features\CreatePost;

use Espo\Core\Exceptions\BadRequest;
use Espo\Core\Exceptions\Conflict;
use Espo\Core\Exceptions\Forbidden;
use Espo\Core\Record\ServiceFactory;
use Espo\Entities\Note;
use Espo\Entities\User;
use Espo\Modules\Mcp\Tools\Feature\Data;
use Espo\Modules\Mcp\Tools\Feature\Utils\ExceptionUtil;
use Espo\Modules\Mcp\Tools\Feature\Utils\ResourceLinkPreparator;
use Espo\Modules\Mcp\Tools\Mcp\Exceptions\InternalError;
use Espo\Modules\Mcp\Tools\Mcp\Schema\General\RootSchema;
use Espo\Modules\Mcp\Tools\Mcp\Schema\Tool\CallToolRequestParams;
use Espo\Modules\Mcp\Tools\Mcp\Schema\Tool\CallToolResult;
use Espo\Modules\Mcp\Tools\Mcp\ToolsCall\ToolProcessor;
use stdClass;

/**
 * @implements ToolProcessor<CreatePostData>
 */
class CreatePostToolProcessor implements ToolProcessor
{
    public function __construct(
        private ServiceFactory $serviceFactory,
        private User $user,
        private ExceptionUtil $exceptionUtil,
        private ResourceLinkPreparator $resourceLinkPreparator,
    ) {}

    public function process(CallToolRequestParams $params, Data $data, ?RootSchema $outputSchema): CallToolResult
    {
        $input = $this->fetchInput($params);

        $service = $this->serviceFactory->createForUser(Note::ENTITY_TYPE, $this->user);

        try {
            $createResult = $service->create($input);
        } catch (BadRequest $e) {
            return $this->exceptionUtil->prepareWriteBadRequestResult($e);
        } catch (Conflict $e) {
            return $this->exceptionUtil->prepareWriteConflictResult($e);
        } catch (Forbidden $e) {
            return $this->exceptionUtil->prepareWriteForbiddenResult($e);
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

    /**
     * @throws InternalError
     */
    private function fetchInput(CallToolRequestParams $params): stdClass
    {
        $parentId = $params->arguments->parentId ?? null;
        $entityType = $params->arguments->parentType ?? null;
        $isInternal = $params->arguments->isInternal ?? false;
        $post = $params->arguments->post ?? null;

        if (!is_string($parentId)) {
            throw new InternalError("No `parentId' provided.");
        }

        if (!is_string($entityType)) {
            throw new InternalError("No `parentType' provided.");
        }

        if (!is_bool($isInternal)) {
            throw new InternalError("Bad `isInternal' value.");
        }

        if ($post !== null && !is_string($post)) {
            throw new InternalError("Bad `post' value.");
        }

        return (object) [
            'parentId' => $parentId,
            'parentType' => $entityType,
            'isInternal' => $isInternal,
            'post' => $post,
        ];
    }
}
