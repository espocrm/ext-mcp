<?php
/************************************************************************
* This file is part of MCP extension for EspoCRM.
*
* MCP extension for EspoCRM.
* Copyright (C) 2026 EspoCRM, Inc.
* Website: https://www.espocrm.com
*
* This program is free software: you can redistribute it and/or modify
* it under the terms of the GNU Affero General Public License as published by
* the Free Software Foundation, either version 3 of the License, or
* (at your option) any later version.
*
* This program is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
* GNU Affero General Public License for more details.
*
* You should have received a copy of the GNU Affero General Public License
* along with this program. If not, see <https://www.gnu.org/licenses/>.
*
* The interactive user interfaces in modified source and object code versions
* of this program must display Appropriate Legal Notices, as required under
* Section 5 of the GNU Affero General Public License version 3.
*
* In accordance with Section 7(b) of the GNU Affero General Public License version 3,
* these Appropriate Legal Notices must retain the display of the "EspoCRM" word.
************************************************************************/

namespace Espo\Modules\Mcp\Tools\Feature\Update;

use Espo\Core\Exceptions\BadRequest;
use Espo\Core\Exceptions\Conflict;
use Espo\Core\Exceptions\Forbidden;
use Espo\Core\Exceptions\NotFound;
use Espo\Core\Record\ServiceFactory;
use Espo\Core\Record\UpdateParams;
use Espo\Entities\User;
use Espo\Modules\Mcp\Tools\Feature\Data;
use Espo\Modules\Mcp\Tools\Feature\Utils\ExceptionUtil;
use Espo\Modules\Mcp\Tools\Feature\Utils\ResourceLinkPreparator;
use Espo\Modules\Mcp\Tools\Mcp\Exceptions\InternalError;
use Espo\Modules\Mcp\Tools\Mcp\Schema\Elicitation\ElicitAction;
use Espo\Modules\Mcp\Tools\Mcp\Schema\Elicitation\ElicitResult;
use Espo\Modules\Mcp\Tools\Mcp\Schema\General\RootSchema;
use Espo\Modules\Mcp\Tools\Mcp\Schema\Tool\CallToolRequestParams;
use Espo\Modules\Mcp\Tools\Mcp\Schema\Tool\CallToolResult;
use Espo\Modules\Mcp\Tools\Mcp\ToolsCall\ToolProcessor;
use Exception;
use RuntimeException;
use stdClass;

/**
 * @implements ToolProcessor<UpdateData>
 */
class UpdateToolProcessor implements ToolProcessor
{
    public const string KEY_CONFIRM_DUPLICATE = 'confirmDuplicate';

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

        if ($this->isDuplicateElicitationCanceled($params)) {
            return new CallToolResult(
                structuredContent: (object) [
                    'error' => (object) [
                        'code' => 409,
                        'message' => "Duplicate record creation is canceled.",
                    ],
                ],
                isError: true,
            );
        }

        $id = $this->fetchId($params);
        $input = $this->fetchInput($params);
        $updateParams = $this->prepareUpdateParams($params);

        try {
            $updateResult = $service->update($id, $input, $updateParams);
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
                    'id' => $updateResult->getEntity()->getId(),
                ],
            ],
            content: [
                $this->resourceLinkPreparator->prepare($updateResult->getEntity()),
            ],
        );
    }

    private function prepareUpdateParams(CallToolRequestParams $params): UpdateParams
    {
        $skipDuplicateCheck = false;

        $confirmDuplicate = $this->getConfirmDuplicateElicitResult($params);

        if ($confirmDuplicate && $confirmDuplicate->action === ElicitAction::Accept) {
            $skipDuplicateCheck = true;
        }

        return (new UpdateParams())->withSkipDuplicateCheck($skipDuplicateCheck);
    }

    private function fetchInput(CallToolRequestParams $params): stdClass
    {
        $input = $params->arguments->record ?? (object) [];

        if (!$input instanceof stdClass) {
            throw new RuntimeException("Bad input.");
        }

        return $input;
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

    private function getConfirmDuplicateElicitResult(CallToolRequestParams $params): ?ElicitResult
    {
        return $params->inputResponses[self::KEY_CONFIRM_DUPLICATE] ?? null;
    }

    private function isDuplicateElicitationCanceled(CallToolRequestParams $params): bool
    {
        $confirmDuplicateElicitResult = $this->getConfirmDuplicateElicitResult($params);

        return $confirmDuplicateElicitResult &&
            (
                $confirmDuplicateElicitResult->action === ElicitAction::Cancel ||
                $confirmDuplicateElicitResult->action === ElicitAction::Decline
            );
    }
}
