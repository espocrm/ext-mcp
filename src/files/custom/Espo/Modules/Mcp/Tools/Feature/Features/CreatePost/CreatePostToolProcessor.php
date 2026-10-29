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
