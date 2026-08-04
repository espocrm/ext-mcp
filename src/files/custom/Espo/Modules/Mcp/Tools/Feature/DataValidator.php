<?php
/**LICENSE**/

namespace Espo\Modules\Mcp\Tools\Feature;

use Espo\Modules\Mcp\Tools\Feature\Validator\Failure;

/**
 * @template TData of Data
 */
interface DataValidator
{
    /**
     * @return Failure[]
     * @param TData $data
     */
    public function validate(Data $data): array;
}
