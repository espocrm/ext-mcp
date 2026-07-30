<?php
/**LICENSE**/

namespace Espo\Modules\Mcp;

/** @noinspection PhpIncludeInspection */
require_once __DIR__ . "/vendor/autoload.php";

use Espo\Core\Binding\Binder;
use Espo\Core\Binding\BindingProcessor;

class Binding implements BindingProcessor
{
    public function process(Binder $binder): void
    {}
}
