<?php
/**LICENSE**/

namespace integration\Espo\Modules\Mcp;

use Espo\Core\Name\Field;
use Espo\Core\ORM\Repository\Option\SaveOption;
use Espo\Modules\Crm\Entities\Lead;
use Espo\Modules\Mcp\Entities\Endpoint;
use Espo\Modules\Mcp\Entities\Feature;
use Espo\Modules\Mcp\Tools\Feature\Find\FindData;
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
