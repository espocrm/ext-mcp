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

namespace Espo\Modules\Mcp\Tools\Feature\Features\Read;

use Espo\Core\Exceptions\Forbidden;
use Espo\Core\Exceptions\NotFound;
use Espo\Core\Record\ReadResult;
use Espo\Core\Record\ServiceFactory;
use Espo\Core\Utils\FieldUtil;
use Espo\Entities\User;
use Espo\Modules\Mcp\Tools\Feature\Data;
use Espo\Modules\Mcp\Tools\Feature\Features\Find\EntityOutput;
use Espo\Modules\Mcp\Tools\Feature\Utils\ResourceLinkPreparator;
use Espo\Modules\Mcp\Tools\JsonSchema\Type\ObjectType;
use Espo\Modules\Mcp\Tools\Mcp\Exceptions\InternalError;
use Espo\Modules\Mcp\Tools\Mcp\Schema\General\RootSchema;
use Espo\Modules\Mcp\Tools\Mcp\Schema\Tool\CallToolRequestParams;
use Espo\Modules\Mcp\Tools\Mcp\Schema\Tool\CallToolResult;
use Espo\Modules\Mcp\Tools\Mcp\ToolsCall\ToolProcessor;
use Espo\ORM\Name\Attribute;
use Exception;
use RuntimeException;
use stdClass;

/**
 * @implements ToolProcessor<ReadData>
 */
class ReadToolProcessor implements ToolProcessor
{
    public function __construct(
        private ServiceFactory $serviceFactory,
        private EntityOutput $entityOutput,
        private User $user,
        private FieldUtil $fieldUtil,
        private ResourceLinkPreparator $resourceLinkPreparator,
    ) {}

    public function process(CallToolRequestParams $params, Data $data, ?RootSchema $outputSchema): CallToolResult
    {
        if (!$outputSchema) {
            throw new InternalError("No output schema.");
        }

        $entityType = $data->entityType;

        $id = $params->arguments->id ?? null;

        if (!is_string($id) || $id === '') {
            throw new InternalError("No `id' provided.");
        }

        try {
            $service = $this->serviceFactory->createForUser($entityType, $this->user);
        } catch (Exception $e) {
            throw new InternalError("Could not create record service for `$entityType`.", previous: $e);
        }

        try {
            $readResult = $service->read($id);
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
        }

        return new CallToolResult(
            structuredContent: (object) [
                'record' => $this->prepareRecordOutput($params, $readResult, $outputSchema),
            ],
            content: [
                $this->resourceLinkPreparator->prepare($readResult->getEntity()),
            ],
        );
    }

    /**
     * @throws InternalError
     */
    private function prepareRecordOutput(
        CallToolRequestParams $params,
        ReadResult $readResult,
        RootSchema $outputSchema,
    ): stdClass {

        $entityType = $readResult->getEntity()->getEntityType();

        $selectAttributes = $this->getSelectAttributes($params, $entityType);

        $recordSchema = $this->getRecordSchema($outputSchema);

        $valueMap = $this->entityOutput->prepare($readResult->getEntity(), $recordSchema);

        if ($selectAttributes !== null) {
            foreach (array_keys(get_object_vars($valueMap)) as $attribute) {
                if (!in_array($attribute, $selectAttributes)) {
                    unset($valueMap->$attribute);
                }
            }
        }

        return $valueMap;
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

        $schema = $outputSchema->getProperties()['record'] ?? null;

        if (!$schema instanceof ObjectType) {
            throw new InternalError("Unexpected schema.");
        }

        return $schema;
    }

    /**
     * @return ?string[]
     */
    private function getSelectAttributes(CallToolRequestParams $params, string $entityType): ?array
    {
        $selectFields = $params->arguments->selectFields ?? null;

        if (!is_array($selectFields)) {
            return null;
        }

        $selectAttributes = [Attribute::ID];

        foreach ($selectFields as $field) {
            if (!is_string($field)) {
                throw new RuntimeException("Non-string value in `select`.");
            }

            array_push($selectAttributes, ...$this->fieldUtil->getAttributeList($entityType, $field));
        }

        return array_values(array_unique($selectAttributes));
    }
}
