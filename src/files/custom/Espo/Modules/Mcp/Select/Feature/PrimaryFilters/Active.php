<?php
/**LICENSE**/

namespace Espo\Modules\Mcp\Select\Feature\PrimaryFilters;

use Espo\Core\Select\Primary\Filter;
use Espo\Modules\Mcp\Entities\Feature;
use Espo\ORM\Query\SelectBuilder;

class Active implements Filter
{
    public function apply(SelectBuilder $queryBuilder): void
    {
        $queryBuilder->where([
            Feature::FIELD_STATUS => Feature::STATUS_ACTIVE,
        ]);
    }
}
