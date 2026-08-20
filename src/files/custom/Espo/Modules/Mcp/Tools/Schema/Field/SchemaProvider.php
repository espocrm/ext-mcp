<?php
/**LICENSE**/

namespace Espo\Modules\Mcp\Tools\Schema\Field;

use Espo\Modules\Mcp\Tools\Schema\Field\FieldSchemaProvider\Params;
use Espo\Modules\Mcp\Tools\Schema\Field\FieldSchemaProvider\Result;

interface SchemaProvider
{
    public function get(Params $params): Result;
}
