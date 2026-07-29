<?php
/**LICENSE**/

namespace Espo\Modules\Mcp\Select\McpEndpoint\PrimaryFilters;

use Espo\Core\Select\Primary\Filter;
use Espo\Modules\Mcp\Entities\McpEndpoint;
use Espo\ORM\Query\SelectBuilder;

class Active implements Filter
{
    public function apply(SelectBuilder $queryBuilder): void
    {
        $queryBuilder->where([
            McpEndpoint::FIELD_STATUS => McpEndpoint::STATUS_ACTIVE,
        ]);
    }
}
