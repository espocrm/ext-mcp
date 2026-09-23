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

namespace Espo\Modules\Mcp\Tools\Schema\FieldFilter;

use Espo\Core\InjectableFactory;
use Espo\Core\Utils\Metadata;
use Espo\Modules\Mcp\Tools\Feature\Exceptions\UnsupportedFeatureValue;
use Espo\Modules\Mcp\Tools\Mcp\BindingProvider;
use Espo\ORM\Defs;

class SchemaProviderFactory
{
    public function __construct(
        private InjectableFactory $injectableFactory,
        private Metadata $metadata,
        private BindingProvider $bindingProvider,
        private Defs $defs,
    ) {}

    /**
     * @throws UnsupportedFeatureValue
     */
    public function create(string $entityType, string $field): SchemaProvider
    {
        $fieldDefs = $this->defs
            ->tryGetEntity($entityType)
            ?->tryGetField($field);

        if (!$fieldDefs) {
            throw new UnsupportedFeatureValue("No field '$entityType.$field'.");
        }

        $type = $fieldDefs->getType();

        /** @var ?class-string<SchemaProvider> $className */
        $className =
            $this->metadata->get("entityDefs.$entityType.fields.$field.mcpFilterSchemaProviderClassName") ??
            $this->metadata->get("app.mcpSchema.fieldTypes.$type.filterSchemaProviderClassName");

        if (!$className) {
            throw new UnsupportedFeatureValue("Unsupported field type '$type'. Field filter '$entityType.$field'.");
        }

        return $this->injectableFactory->createWithBinding($className, $this->bindingProvider->get());
    }
}
