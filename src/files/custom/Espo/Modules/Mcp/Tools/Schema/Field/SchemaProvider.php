<?php
/**LICENSE**/

namespace Espo\Modules\Mcp\Tools\Schema\Field;

use Espo\Modules\Mcp\Tools\Schema\Field\FieldSchemaProvider\Params;
use Espo\Modules\Mcp\Tools\Schema\Field\FieldSchemaProvider\Result;

/**
 * @todo When using for a writing action, check readOnly. Skip. Check readOnlyAfterCreate.
 * @todo Check edit field access when using for writing action. SKip.
 */
interface SchemaProvider
{
    public function get(Params $params): Result;
}
