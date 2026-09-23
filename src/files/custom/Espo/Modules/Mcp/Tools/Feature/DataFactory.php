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

namespace Espo\Modules\Mcp\Tools\Feature;

use Espo\Core\Utils\Metadata;
use Espo\Modules\Mcp\Entities\Feature;
use Espo\Modules\Mcp\Tools\Feature\Exceptions\BadFeatureData;
use Espo\Modules\Mcp\Tools\Feature\Exceptions\UnsupportedType;
use InvalidArgumentException;
use UnexpectedValueException;

class DataFactory
{
    public function __construct(
        private Metadata $metadata,
    ) {}

    /**
     * @throws UnsupportedType
     * @throws BadFeatureData
     */
    public function createForFeature(Feature $feature): Data
    {
        try {
            $type = $feature->getType();
        } catch (UnexpectedValueException $e) {
            throw new BadFeatureData("No type.", previous: $e);
        }

        try {
            $rawData = $feature->getRawData();
        } catch (UnexpectedValueException $e) {
            throw new BadFeatureData("No data.", previous: $e);
        }

        /** @var ?class-string<Data> $className */
        $className = $this->metadata->get("app.mcpFeatures.$type.record.dataClassName");

        if (!$className) {
            throw new UnsupportedType("Unsupported type '$type'.");
        }

        try {
            return $className::fromRaw($rawData);
        } catch (InvalidArgumentException $e) {
            $id = $feature->hasId() ? $feature->getId() : '?';

            throw new BadFeatureData("Bad data in feature record '$id'.", previous: $e);
        }
    }
}
