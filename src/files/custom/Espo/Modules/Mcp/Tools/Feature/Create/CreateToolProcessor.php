<?php
/**LICENSE**/

namespace Espo\Modules\Mcp\Tools\Feature\Create;

use Espo\Core\Exceptions\BadRequest;
use Espo\Core\Exceptions\Conflict;
use Espo\Core\Exceptions\Forbidden;
use Espo\Core\FieldValidation\Exceptions\ValidationError;
use Espo\Core\Name\Field;
use Espo\Core\Record\CreateResult;
use Espo\Core\Record\ServiceFactory;
use Espo\Core\Utils\Config\ApplicationConfig;
use Espo\Entities\User;
use Espo\Modules\Mcp\Tools\Feature\Data;
use Espo\Modules\Mcp\Tools\Mcp\Exceptions\InternalError;
use Espo\Modules\Mcp\Tools\Mcp\Schema\General\RootSchema;
use Espo\Modules\Mcp\Tools\Mcp\Schema\Resource\ResourceLink;
use Espo\Modules\Mcp\Tools\Mcp\Schema\Tool\CallToolRequestParams;
use Espo\Modules\Mcp\Tools\Mcp\Schema\Tool\CallToolResult;
use Espo\Modules\Mcp\Tools\Mcp\ToolsCall\ToolProcessor;
use Exception;

/**
 * @implements ToolProcessor<CreateData>
 */
class CreateToolProcessor implements ToolProcessor
{
    public function __construct(
        private ServiceFactory $serviceFactory,
        private User $user,
        private ApplicationConfig $applicationConfig,
    ) {}

    public function process(CallToolRequestParams $params, Data $data, ?RootSchema $outputSchema): CallToolResult
    {
        $entityType = $data->entityType;

        try {
            $service = $this->serviceFactory->createForUser($entityType, $this->user);
        } catch (Exception $e) {
            throw new InternalError("Could not create record service for `$entityType`.", previous: $e);
        }

        $input = $params->arguments ?? (object) [];

        try {
            $createResult = $service->create($input);
        } catch (BadRequest $e) {
            $message = 'Bad request.';

            if ($e instanceof ValidationError) {
                // @todo Test.
                $message = "Validation error.";

                if ($e->hasFailure()) {
                    $failure = $e->getFailure();

                    $message .= " Field: `{$failure->getField()}`. Type: `{$failure->getType()}`.";
                }
            }

            return new CallToolResult(
                structuredContent: (object) [
                    'error' => (object) [
                        'code' => 400,
                        'message' => $message,
                    ],
                ],
                isError: true,
            );
        } catch (Forbidden) {
            return new CallToolResult(
                structuredContent: (object) [
                    'error' => (object) [
                        'code' => 403,
                        'message' => 'No read access to the record.',
                    ],
                ],
                isError: true,
            );
        } catch (Conflict) {
            // @todo Process duplicate handling.

            return new CallToolResult(
                structuredContent: (object) [
                    'error' => (object) [
                        'code' => 409,
                        'message' => 'Conflict occurred.',
                    ],
                ],
                isError: true,
            );
        }

        return new CallToolResult(
            structuredContent: (object) [
                'record' => (object) [
                    'id' => $createResult->getEntity()->getId(),
                ],
            ],
            content: [
                $this->prepareResourceLinkRecordUrl($createResult),
            ],
        );
    }

    private function prepareResourceLinkRecordUrl(CreateResult $createResult): ResourceLink
    {
        $entity = $createResult->getEntity();

        $entityType = $entity->getEntityType();
        $id = $entity->getId();

        $title = $entity->get(Field::NAME);

        if (!is_string($title)) {
            $title = $id;
        }

        $url = $this->applicationConfig->getSiteUrl() . "#$entityType/view/$id";

        return new ResourceLink(
            name: "$entityType/$id",
            uri: $url,
            title: $title,
            description: "Link to the record in the CRM.",
        );
    }
}
