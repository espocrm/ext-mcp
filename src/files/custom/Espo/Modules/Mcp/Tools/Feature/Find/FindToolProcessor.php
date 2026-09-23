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

namespace Espo\Modules\Mcp\Tools\Feature\Find;

use Espo\Core\Exceptions\BadRequest;
use Espo\Core\Exceptions\Forbidden;
use Espo\Core\Record\Collection;
use Espo\Core\Record\ServiceFactory;
use Espo\Core\Select\SearchParams;
use Espo\Core\Utils\FieldUtil;
use Espo\Entities\User;
use Espo\Modules\Mcp\Tools\Feature\Data;
use Espo\Modules\Mcp\Tools\JsonSchema\Type\ArrayType;
use Espo\Modules\Mcp\Tools\JsonSchema\Type\ObjectType;
use Espo\Modules\Mcp\Tools\Mcp\Exceptions\InternalError;
use Espo\Modules\Mcp\Tools\Mcp\Schema\General\RootSchema;
use Espo\Modules\Mcp\Tools\Mcp\Schema\Tool\CallToolRequestParams;
use Espo\Modules\Mcp\Tools\Mcp\Schema\Tool\CallToolResult;
use Espo\Modules\Mcp\Tools\Mcp\ToolsCall\ToolProcessor;
use Espo\ORM\Entity;
use Exception;
use RuntimeException;
use stdClass;

/**
 * @implements ToolProcessor<FindData>
 */
class FindToolProcessor implements ToolProcessor
{
    public function __construct(
        private ServiceFactory $serviceFactory,
        private EntityOutput $entityOutput,
        private User $user,
        private FieldUtil $fieldUtil,
    ) {}

    public function process(CallToolRequestParams $params, Data $data, ?RootSchema $outputSchema): CallToolResult
    {
        if (!$outputSchema) {
            throw new InternalError("No output schema.");
        }

        $entityType = $data->entityType;

        $searchParams = $this->prepareSearchParams($entityType, $params);

        try {
            $service = $this->serviceFactory->createForUser($entityType, $this->user);
        } catch (Exception $e) {
            throw new InternalError("Could not create record service for `$entityType`.", previous: $e);
        }

        try {
            $recordCollection = $service->find($searchParams);
        } catch (Forbidden $e) {
            $message = "No access.";

            if ($e->getMessage()) {
                $message .= ' ' . $e->getMessage();
            }

            return new CallToolResult(
                structuredContent: (object) [
                    'error' => (object) [
                        'code' => 403,
                        'message' => $message,
                    ],
                ],
                isError: true,
            );
        } catch (BadRequest $e) {
            $message = "Bad request.";

            if ($e->getMessage()) {
                $message .= ' ' . $e->getMessage();
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

        $output = (object) [
            'records' => $this->prepareList($recordCollection, $outputSchema),
            'total' => $recordCollection->getTotal(),
        ];

        return new CallToolResult(
            structuredContent: $output,
        );
    }

    private function prepareSearchParams(string $entityType, CallToolRequestParams $params): SearchParams
    {
        $raw = clone ($params->arguments ?? (object) []);

        $selectFields = $raw->selectFields ?? null;

        if (is_array($selectFields)) {
            $selectAttributes = [];

            foreach ($selectFields as $field) {
                if (!is_string($field)) {
                    throw new RuntimeException("Non-string value in `select`.");
                }

                array_push($selectAttributes, ...$this->fieldUtil->getAttributeList($entityType, $field));
            }

            $raw->select = array_values(array_unique($selectAttributes));
        }

        $searchParams = SearchParams::fromRaw($raw);

        if ($searchParams->getMaxSize() === null) {
            $searchParams = $searchParams->withMaxSize(FindToolDefinitionProvider::MAX_SIZE_LIMIT);
        }

        if ($searchParams->getMaxSize() > FindToolDefinitionProvider::MAX_SIZE_LIMIT) {
            throw new RuntimeException("Max size exceeds limit.");
        }

        return $searchParams;
    }

    /**
     * @param Collection<Entity> $recordCollection
     * @return stdClass[]
     * @throws InternalError
     */
    private function prepareList(Collection $recordCollection, RootSchema $outputSchema): array
    {
        $recordSchema = $this->getRecordSchema($outputSchema);

        $output = [];

        foreach ($recordCollection->getCollection() as $entity) {
            $output[] = $this->entityOutput->prepare($entity, $recordSchema);
        }

        return $output;
    }

    /**
     * @throws InternalError
     */
    private function getRecordSchema(RootSchema $outputSchema): ObjectType
    {
        $outputSchema = $outputSchema->getSchema();

        if (!$outputSchema instanceof ObjectType) {
            throw new InternalError("Unexpected schema.");
        }

        $listSchema = $outputSchema->getProperties()['records'] ?? null;

        if (!$listSchema instanceof ArrayType) {
            throw new InternalError("Unexpected schema.");
        }

        $itemsSchema = $listSchema->getItems();

        if (!$itemsSchema instanceof ObjectType) {
            throw new InternalError("Unexpected schema.");
        }

        return $itemsSchema;
    }
}
