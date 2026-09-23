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

namespace Espo\Modules\Mcp\Classes\RecordHooks\Feature;

use Espo\Core\Exceptions\Conflict;
use Espo\Core\Exceptions\Error\Body;
use Espo\Core\Record\Hook\SaveHook;
use Espo\Modules\Mcp\Entities\Feature;
use Espo\Modules\Mcp\Tools\Feature\DataFactory;
use Espo\Modules\Mcp\Tools\Feature\Exceptions\BadFeatureData;
use Espo\Modules\Mcp\Tools\Feature\Exceptions\UnsupportedType;
use Espo\ORM\Entity;
use Espo\ORM\EntityManager;

/**
 * @implements SaveHook<Feature>
 */
class BeforeSaveValidation implements SaveHook
{
    public function __construct(
        private EntityManager $entityManager,
        private DataFactory $dataFactory,
    ) {}

    public function process(Entity $entity): void
    {
        $this->validateSameExists($entity);
    }

    /**
     * @throws Conflict
     */
    private function validateSameExists(Feature $entity): void
    {
        if (
            !$entity->isAttributeChanged(Feature::FIELD_DATA) &&
            !$entity->isAttributeChanged(Feature::FIELD_TYPE)
        ) {
            return;
        }

        $endpoint = $entity->getEndpoint();

        $features = $this->entityManager
            ->getRDBRepositoryByClass(Feature::class)
            ->sth()
            ->where([
                Feature::LINK_ENDPOINT . 'Id' => $endpoint->getId()
            ])
            ->find();

        foreach ($features as $feature) {
            if (!$entity->isNew() && $feature->getId() === $entity->getId()) {
                continue;
            }

            if ($entity->getType() !== $feature->getType()) {
                continue;
            }

            try {
                $data = $this->dataFactory->createForFeature($entity);
                $dataOther = $this->dataFactory->createForFeature($feature);
            } catch (BadFeatureData|UnsupportedType) {
                continue;
            }

            if ($data->getKey() === $dataOther->getKey()) {
                throw Conflict::createWithBody(
                    'featureAlreadyExists',
                    Body::create()->withMessageTranslation('featureAlreadyExists', Feature::ENTITY_TYPE)
                );
            }
        }
    }
}
