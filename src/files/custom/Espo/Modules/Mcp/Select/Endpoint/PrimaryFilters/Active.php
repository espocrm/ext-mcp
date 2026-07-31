<?php
/**LICENSE**/

namespace Espo\Modules\Mcp\Select\Endpoint\PrimaryFilters;

use Espo\Core\Select\Primary\Filter;
use Espo\Modules\Mcp\Entities\Endpoint;
use Espo\ORM\Query\SelectBuilder;

class Active implements Filter
{
    public function apply(SelectBuilder $queryBuilder): void
    {
        $queryBuilder->where([
            Endpoint::FIELD_STATUS => Endpoint::STATUS_ACTIVE,
        ]);
    }
}
