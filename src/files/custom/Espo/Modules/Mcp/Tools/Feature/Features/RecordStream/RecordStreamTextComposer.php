<?php
/**LICENSE**/

namespace Espo\Modules\Mcp\Tools\Feature\Features\RecordStream;

use Espo\Modules\Mcp\Tools\Feature\Data;
use Espo\Modules\Mcp\Tools\Feature\TextComposer;

/**
 * @implements TextComposer<RecordStreamData>
 */
class RecordStreamTextComposer implements TextComposer
{
    public function compose(Data $data): null
    {
        return null;
    }
}
