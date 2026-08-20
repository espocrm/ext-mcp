<?php
/**LICENSE**/

namespace Espo\Modules\Mcp\Tools\Feature\Utils;

use Espo\Core\Exceptions\BadRequest;
use Espo\Core\Exceptions\Conflict;
use Espo\Core\Exceptions\Forbidden;
use Espo\Core\FieldValidation\Exceptions\ValidationError;
use Espo\Core\Record\Exceptions\DuplicateConflict;
use Espo\Core\Utils\Json;
use Espo\Core\Utils\Language;
use Espo\Modules\Mcp\Tools\Feature\Update\UpdateToolProcessor;
use Espo\Modules\Mcp\Tools\JsonSchema\Type\ObjectType;
use Espo\Modules\Mcp\Tools\Mcp\Schema\Elicitation\ElicitRequest;
use Espo\Modules\Mcp\Tools\Mcp\Schema\Elicitation\ElicitRequestFormParams;
use Espo\Modules\Mcp\Tools\Mcp\Schema\General\RootObjectSchema;
use Espo\Modules\Mcp\Tools\Mcp\Schema\Resource\ResourceLink;
use Espo\Modules\Mcp\Tools\Mcp\Schema\Tool\CallToolResult;
use JsonException;
use stdClass;

class ExceptionUtil
{
    public function __construct(
        private Language $defaultLanguage,
        private ResourceLinkPreparator $resourceLinkPreparator,
    ) {}

    /**
     * @return ResourceLink[]
     */
    private function prepareDuplicateLinks(DuplicateConflict $e): array
    {
        return array_map(function ($entity) {
            return $this->resourceLinkPreparator->prepare(
                entity: $entity,
                description: "Link to the duplicate record in the CRM.",
            );
        }, iterator_to_array($e->getDuplicates()));
    }

    public function prepareWriteConflictResult(Conflict $e): CallToolResult
    {
        if ($e instanceof DuplicateConflict) {
            return new CallToolResult(
                content: $this->prepareDuplicateLinks($e),
                inputRequests: [
                    UpdateToolProcessor::KEY_CONFIRM_DUPLICATE => new ElicitRequest(
                        params: new ElicitRequestFormParams(
                            message: "The record might be a duplicate. Proceed anyway?",
                            requestedSchema: new RootObjectSchema(
                                schema: new ObjectType(),
                            ),
                        ),
                    ),
                ],
            );
        }

        $message = $this->appendBodyMessage("Conflict occurred.", $e);

        return new CallToolResult(
            structuredContent: (object) [
                'error' => (object) [
                    'code' => 409,
                    'message' => $message,
                ],
            ],
            isError: true,
        );
    }

    public function prepareWriteForbiddenResult(Forbidden $exception): CallToolResult
    {
        $message = "No access.";

        if ($exception->getBody()) {
            $message = $this->appendBodyMessage($message, $exception);
        } else if ($exception->getMessage()) {
            $message .= ' ' . $exception->getMessage();
        }

        return new CallToolResult(
            structuredContent: (object) [
                'error' => (object) [
                    'code' => 403,
                    'message' => $message
                ],
            ],
            isError: true,
        );
    }

    public function prepareWriteBadRequestResult(BadRequest $exception): CallToolResult
    {
        $message = $this->appendBodyMessage("Bad request.", $exception);

        if ($exception instanceof ValidationError) {
            $message = "Validation error.";

            if ($exception->hasFailure()) {
                $failure = $exception->getFailure();

                $message .=
                    " Problem field: `{$failure->getField()}`." .
                    " Failed validation name: `{$failure->getType()}`.";
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
    }

    public function getBodyMessage(Conflict|BadRequest|Forbidden $exception): ?string
    {
        $body = $exception->getBody();

        if (!$body) {
            return null;
        }

        try {
            $data = Json::decode($body);
        } catch (JsonException) {
            return null;
        }

        if (!$data instanceof stdClass) {
            return null;
        }

        $messageTranslation = $data->messageTranslation ?? null;

        if (!$messageTranslation instanceof stdClass) {
            return null;
        }

        $label = $messageTranslation->label ?? null;
        $scope = $messageTranslation->scope ?? null;
        $replaceDataRaw = $messageTranslation->data ?? (object) [];

        if (!is_string($label)) {
            return null;
        }

        if ($scope !== null && !is_string($scope)) {
            return null;
        }

        if (!$replaceDataRaw instanceof stdClass) {
            return null;
        }

        $message = $this->defaultLanguage->translateLabel($label, 'messages', $scope ?? 'Global');

        $replaceData = [];

        foreach (get_object_vars($replaceDataRaw) as $k => $v) {
            $replaceData['{' . $k . '}'] = $v;
        }

        return strtr($message, $replaceData);
    }

    public function appendBodyMessage(string $message, Conflict|BadRequest|Forbidden $exception): string
    {
        $part = $this->getBodyMessage($exception);

        if (!$part) {
            return $message;
        }

        return $message . ' ' . $part;
    }
}
