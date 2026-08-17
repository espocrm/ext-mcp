<?php
/**LICENSE**/

namespace Espo\Modules\Mcp\Tools\Feature\Create;

use Espo\Core\Exceptions\BadRequest;
use Espo\Core\Exceptions\Conflict;
use Espo\Core\Exceptions\Forbidden;
use Espo\Core\FieldValidation\Exceptions\ValidationError;
use Espo\Core\Name\Field;
use Espo\Core\Record\CreateParams;
use Espo\Core\Record\CreateResult;
use Espo\Core\Record\Exceptions\DuplicateConflict;
use Espo\Core\Record\ServiceFactory;
use Espo\Core\Utils\Config\ApplicationConfig;
use Espo\Entities\User;
use Espo\Modules\Mcp\Tools\Feature\Data;
use Espo\Modules\Mcp\Tools\JsonSchema\Type\ObjectType;
use Espo\Modules\Mcp\Tools\Mcp\Exceptions\InternalError;
use Espo\Modules\Mcp\Tools\Mcp\Schema\Elicitation\ElicitAction;
use Espo\Modules\Mcp\Tools\Mcp\Schema\Elicitation\ElicitRequest;
use Espo\Modules\Mcp\Tools\Mcp\Schema\Elicitation\ElicitRequestFormParams;
use Espo\Modules\Mcp\Tools\Mcp\Schema\General\RootObjectSchema;
use Espo\Modules\Mcp\Tools\Mcp\Schema\General\RootSchema;
use Espo\Modules\Mcp\Tools\Mcp\Schema\Resource\ResourceLink;
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
    private const string KEY_CONFIRM_DUPLICATE = 'confirmDuplicate';

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

        $input = $this->fetchInput($params);
        $createParams = $this->prepareCreateParams($params);

        try {
            $createResult = $service->create($input, $createParams);
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
        } catch (Conflict $e) {
            if ($e instanceof DuplicateConflict) {
                // @todo Add links to duplicate records.

                return new CallToolResult(
                    inputRequests: [
                        self::KEY_CONFIRM_DUPLICATE => new ElicitRequest(
                            params: new ElicitRequestFormParams(
                                message: "The record being created might be a duplicate. Create anyway?",
                                requestedSchema: new RootObjectSchema(
                                    schema: new ObjectType(),
                                ),
                            ),
                        ),
                    ],
                );
            }

            return new CallToolResult(
                structuredContent: (object) [
                    'error' => (object) [
                        'code' => 409,
                        'message' => "Conflict occurred.",
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
