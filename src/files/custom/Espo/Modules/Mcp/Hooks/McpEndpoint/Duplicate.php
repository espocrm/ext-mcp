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

namespace Espo\Modules\Mcp\Hooks\McpEndpoint;

use Espo\Core\Hook\Hook\AfterSave;
use Espo\Core\ORM\Repository\Option\SaveOption;
use Espo\Modules\Mcp\Entities\Endpoint;
use Espo\Modules\Mcp\Entities\Feature;
use Espo\ORM\Entity;
use Espo\ORM\EntityManager;
use Espo\ORM\Name\Attribute;
use Espo\ORM\Repository\Option\SaveOptions;

/**
 * @implements AfterSave<Endpoint>
 */
class Duplicate implements AfterSave
{
    public function __construct(
        private EntityManager $entityManager,
    ) {}

    public function afterSave(Entity $entity, SaveOptions $options): void
    {
        $id = $options->get(SaveOption::DUPLICATE_SOURCE_ID);

        if (!$entity->isNew() || !$id) {
            return;
        }

        $original = $this->entityManager->getRDBRepositoryByClass(Endpoint::class)->getById($id);

        if (!$original) {
            return;
        }

        $this->processCopy($original, $entity);
    }

    private function processCopy(Endpoint $original, Endpoint $endpoint): void
    {
        foreach ($original->getFeatures() as $originalFeature) {
            $this->processCopyFeature($originalFeature, $endpoint);
        }
    }

    private function processCopyFeature(Feature $originalFeature, Endpoint $endpoint): void
    {
        $feature = $this->entityManager->getRDBRepositoryByClass(Feature::class)->getNew();

        $values = $originalFeature->getValueMap();

        unset($values->{Attribute::ID});
        unset($values->{Feature::ATTR_ENDPOINT_ID});

        $feature->setMultiple($values);
        $feature->setEndpoint($endpoint);

        $this->entityManager->saveEntity($feature);
    }
}
