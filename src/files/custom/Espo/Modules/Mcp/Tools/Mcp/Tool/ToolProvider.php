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

namespace Espo\Modules\Mcp\Tools\Mcp\Tool;

use Espo\Modules\Mcp\Entities\Endpoint;
use Espo\Modules\Mcp\Entities\Feature;
use Espo\Modules\Mcp\Tools\Feature\DataFactory;
use Espo\Modules\Mcp\Tools\Feature\Exceptions\BadFeatureData;
use Espo\Modules\Mcp\Tools\Feature\Exceptions\NoUserAccess;
use Espo\Modules\Mcp\Tools\Feature\Exceptions\UnsupportedFeatureValue;
use Espo\Modules\Mcp\Tools\Feature\Exceptions\UnsupportedType;
use Espo\Modules\Mcp\Tools\Feature\ToolDefinitionProviderFactory;
use Espo\Modules\Mcp\Tools\Mcp\Exceptions\InternalError;
use Espo\Modules\Mcp\Tools\Mcp\Exceptions\InvalidParamsError;
use Espo\Modules\Mcp\Tools\Mcp\Schema\Tool\Tool;

class ToolProvider
{
    private const int NAME_MAX_LENGTH = 128;

    public function __construct(
        private Endpoint $endpoint,
        private DataFactory $dataFactory,
        private ToolDefinitionProviderFactory $toolSchemaProviderFactory,
    ) {}

    /**
     * @throws InternalError
     * @throws InvalidParamsError
     */
    public function get(string $name): ToolEnvelope
    {
        foreach ($this->getEnvelopeAll() as $item) {
            if ($item->tool->name !== $name) {
                continue;
            }

            return $item;
        }

        throw new InvalidParamsError("Tool `$name` not found.");
    }

    /**
     * @return Tool[]
     * @throws InternalError
     */
    public function getAll(): array
    {
        $tools = [];

        foreach ($this->getEnvelopeAll() as $item) {
            $tools[] = $item->tool;
        }

        return $tools;
    }

    /**
     * @todo Cache. For user and endpoint.
     *
     * @return ToolEnvelope[]
     * @throws InternalError
     */
    private function getEnvelopeAll(): array
    {
        $tools = [];

        foreach ($this->endpoint->getFeatures() as $feature) {
            $tool = $this->getForFeature($feature);

            if (!$tool) {
                continue;
            }

            $tools[] = new ToolEnvelope(
                tool: $tool,
                featureId: $feature->getId(),
            );
        }

        return $tools;
    }

    /**
     * @throws InternalError
     */
    private function getForFeature(Feature $feature): ?Tool
    {
        try {
            $data = $this->dataFactory->createForFeature($feature);
        } catch (BadFeatureData|UnsupportedType $e) {
            throw new InternalError("Could not create data object.", previous: $e);
        }

        try {
            $provider = $this->toolSchemaProviderFactory->create($feature->getType());
        } catch (UnsupportedType $e) {
            throw new InternalError("Could not create tool schema provider.", previous: $e);
        }

        try {
            $tool = $provider->get($data);
        } catch (UnsupportedFeatureValue $e) {
            throw new InternalError("Unsupported feature value.", previous: $e);
        } catch (NoUserAccess) {
            return null;
        }

        if (strlen($tool->name) > self::NAME_MAX_LENGTH) {
            throw new InternalError("Tool `$tool->name` name length should be not longer than 64 characters.");
        }

        return $tool;
    }
}
