<?php
/**LICENSE**/

namespace Espo\Modules\Mcp;

require_once __DIR__ . "/vendor/autoload.php";

use Espo\Core\Binding\Binder;
use Espo\Core\Binding\BindingProcessor;

class Binding implements BindingProcessor
{
    public function process(Binder $binder): void
    {
        $a = new Vendor\Mcp\Schema\Prompt('test');
    }
}
