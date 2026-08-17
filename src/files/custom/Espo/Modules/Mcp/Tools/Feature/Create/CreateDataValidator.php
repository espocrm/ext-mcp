<?php
/**LICENSE**/

namespace Espo\Modules\Mcp\Tools\Feature\Create;

use Espo\Modules\Mcp\Tools\Feature\Data;
use Espo\Modules\Mcp\Tools\Feature\DataValidator;

/**
 * @implements DataValidator<CreateData>
 */
class CreateDataValidator implements DataValidator
{
    public function validate(Data $data): array
    {
        return [];
    }
}
