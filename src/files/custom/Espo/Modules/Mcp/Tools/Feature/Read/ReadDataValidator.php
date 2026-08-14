<?php
/**LICENSE**/

namespace Espo\Modules\Mcp\Tools\Feature\Read;

use Espo\Modules\Mcp\Tools\Feature\Data;
use Espo\Modules\Mcp\Tools\Feature\DataValidator;
use Espo\Modules\Mcp\Tools\Feature\Find\FindData;

/**
 * @implements DataValidator<FindData>
 */
class ReadDataValidator implements DataValidator
{
    public function validate(Data $data): array
    {
        return [];
    }
}
