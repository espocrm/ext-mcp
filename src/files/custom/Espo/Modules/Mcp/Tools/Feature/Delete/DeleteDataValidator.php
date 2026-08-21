<?php
/**LICENSE**/

namespace Espo\Modules\Mcp\Tools\Feature\Delete;

use Espo\Modules\Mcp\Tools\Feature\Data;
use Espo\Modules\Mcp\Tools\Feature\DataValidator;

/**
 * @implements DataValidator<DeleteData>
 */
class DeleteDataValidator implements DataValidator
{
    public function validate(Data $data): array
    {
        return [];
    }
}
