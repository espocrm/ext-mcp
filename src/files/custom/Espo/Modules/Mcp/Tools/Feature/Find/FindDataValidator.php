<?php
/**LICENSE**/

namespace Espo\Modules\Mcp\Tools\Feature\Find;

use Espo\Modules\Mcp\Tools\Feature\Data;
use Espo\Modules\Mcp\Tools\Feature\DataValidator;
use Espo\Modules\Mcp\Tools\Feature\Validator\Failure;

/**
 * @implements DataValidator<FindData>
 */
class FindDataValidator implements DataValidator
{
    /**
     * @return Failure[]
     */
    public function validate(Data $data): array
    {
        return [];
    }
}
