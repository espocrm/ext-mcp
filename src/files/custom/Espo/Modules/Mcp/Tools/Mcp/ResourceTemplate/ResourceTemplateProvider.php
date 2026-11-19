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

namespace Espo\Modules\Mcp\Tools\Mcp\ResourceTemplate;

use Espo\Core\Binding\BindingContainerBuilder;
use Espo\Core\InjectableFactory;
use Espo\Modules\Mcp\Entities\Endpoint;
use Espo\Modules\Mcp\Tools\Mcp\Schema\Resource\ResourceTemplate;
use Espo\Modules\Mcp\Tools\Mcp\Util\Cache\DataCacheAccess;

class ResourceTemplateProvider
{
    /**
     * @param DataCacheAccess<ResourceTemplate[]> $dataCacheAccess
     */
    public function __construct(
        private DataCacheAccess $dataCacheAccess,
        private CacheKeyProvider $cacheKeyProvider,
        private MetadataProvider $metadataProvider,
        private InjectableFactory $injectableFactory,
        private Endpoint $endpoint,
    ) {
        $this->dataCacheAccess->init(
            key: $this->cacheKeyProvider->get(),
            loader: fn () => $this->load(),
        );
    }

    /**
     * @return ResourceTemplate[]
     */
    public function getAll(): array
    {
        return $this->dataCacheAccess->get();
    }

    /**
     * @return ResourceTemplate[]
     */
    private function load(): array
    {
        $list = [];

        $binding = BindingContainerBuilder::create()
            ->bindInstance(Endpoint::class, $this->endpoint)
            ->build();

        foreach ($this->metadataProvider->getAll() as $defs) {
            $className = $defs['definitionProviderClassName'];

            $provider = $this->injectableFactory->createWithBinding($className, $binding);

            $list[] = $provider->get();
        }

        return $list;
    }
}
