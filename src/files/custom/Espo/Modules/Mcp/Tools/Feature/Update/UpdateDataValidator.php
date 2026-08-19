<?php
/**LICENSE**/

namespace Espo\Modules\Mcp\Tools\Feature\Update;

use Espo\Modules\Mcp\Tools\Feature\Data;
use Espo\Modules\Mcp\Tools\Feature\DataValidator;

/**
 * @implements DataValidator<UpdateData>
 */
class UpdateDataValidator implements DataValidator
{
    public function validate(Data $data): array
    {
        return [];
    }
}
