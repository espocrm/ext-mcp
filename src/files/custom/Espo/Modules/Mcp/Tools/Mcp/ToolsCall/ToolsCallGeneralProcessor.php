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

namespace Espo\Modules\Mcp\Tools\Mcp\ToolsCall;

use Espo\Modules\Mcp\Entities\Feature;
use Espo\Modules\Mcp\Tools\Feature\DataFactory;
use Espo\Modules\Mcp\Tools\Feature\Exceptions\BadFeatureData;
use Espo\Modules\Mcp\Tools\Feature\Exceptions\UnsupportedType;
use Espo\Modules\Mcp\Tools\Mcp\JsonSchemaValidator\Validator;
use Espo\Modules\Mcp\Tools\Mcp\Exceptions\InternalError;
use Espo\Modules\Mcp\Tools\Mcp\Exceptions\InvalidParamsError;
use Espo\Modules\Mcp\Tools\Mcp\Schema\Tool\CallToolRequestParams;
use Espo\Modules\Mcp\Tools\Mcp\Schema\Tool\CallToolResult;
use Espo\Modules\Mcp\Tools\Mcp\Tool\ToolProvider;
use Espo\ORM\EntityManager;

class ToolsCallGeneralProcessor
{
    public function __construct(
        private ToolProvider $toolProvider,
        private Validator $jsonSchemaValidator,
        private ToolProcessorFactory $processorFactory,
        private EntityManager $entityManager,
        private DataFactory $dataFactory,
    ) {}

    /**
     * @throws InternalError
     * @throws InvalidParamsError
     */
    public function process(CallToolRequestParams $params): CallToolResult
    {
        $toolEnvelope = $this->toolProvider->get($params->name);

        $feature = $this->getFeature($toolEnvelope->featureId, $params->name);

        $this->jsonSchemaValidator->assert($toolEnvelope->tool->inputSchema, $params->arguments);

        $processor = $this->processorFactory->create($params->name);

        try {
            $data = $this->dataFactory->createForFeature($feature);
        } catch (BadFeatureData|UnsupportedType $e) {
            throw new InternalError("Could not create data object.", previous: $e);
        }

        return $processor->process($params, $data, $toolEnvelope->tool->outputSchema);
    }

    /**
     * @throws InternalError
     * @throws InvalidParamsError
     */
    private function getFeature(string $id, string $name): Feature
    {
        $feature = $this->entityManager->getRDBRepositoryByClass(Feature::class)->getById($id);

        if (!$feature) {
            throw new InternalError("Feature `$id` for tool `$name` not found.");
        }

        if (!$feature->isActive()) {
            throw new InvalidParamsError("Tool `$name` not found.");
        }

        return $feature;
    }
}
