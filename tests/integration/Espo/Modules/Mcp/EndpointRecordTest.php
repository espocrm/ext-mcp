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

namespace integration\Espo\Modules\Mcp;

use Espo\Core\Name\Field;
use Espo\Core\ORM\Repository\Option\SaveOption;
use Espo\Modules\Crm\Entities\Lead;
use Espo\Modules\Mcp\Entities\Endpoint;
use Espo\Modules\Mcp\Entities\Feature;
use Espo\Modules\Mcp\Tools\Feature\Features\Find\FindData;
use tests\integration\Core\BaseTestCase;

class EndpointRecordTest extends BaseTestCase
{
    public function testDuplicate(): void
    {
        $em = $this->getEntityManager();

        $original = $em->getRDBRepositoryByClass(Endpoint::class)->getNew();
        $original->setSlug('test');
        $em->saveEntity($original);

        $em->saveEntity(
            $em->getRDBRepositoryByClass(Feature::class)->getNew()
                ->setType(FindData::TYPE)
                ->setData(
                    new FindData(
                        entityType: Lead::ENTITY_TYPE,
                        textFilter: true,
                        selectFields: [
                            new FindData\Field(Field::NAME),
                        ],
                        primaryFilters: ['actual'],
                        boolFilters: ['onlyMy'],
                        filterFields: [
                            new FindData\Field('status'),
                        ],
                    )
                )
                ->setEndpoint($original)
        );

        //

        $copy = $em->getRDBRepositoryByClass(Endpoint::class)->getNew();
        $copy->setSlug('copy');

        $em->saveEntity($copy, [
            SaveOption::DUPLICATE_SOURCE_ID => $original->getId(),
        ]);

        $em->refreshEntity($original);
        $em->refreshEntity($copy);

        $this->assertEquals(1, $original->getFeatures()->count());
        $this->assertEquals(1, $copy->getFeatures()->count());
        /** @noinspection PhpPossiblePolymorphicInvocationInspection */
        $this->assertEquals(FindData::TYPE, $copy->getFeatures()[0]->getType());
    }
}
