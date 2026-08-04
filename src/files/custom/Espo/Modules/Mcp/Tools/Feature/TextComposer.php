<?php
/**LICENSE**/

namespace Espo\Modules\Mcp\Tools\Feature;

/**
 * @template TData of Data
 */
interface TextComposer
{
    /**
     * @param TData $data
     */
    public function compose(Data $data): string;
}
