<?php
/**LICENSE**/

namespace Espo\Modules\Mcp\Tools\Schema\Field;

use Espo\Modules\Mcp\Tools\JsonSchema\Schema;
use Espo\Modules\Mcp\Tools\Schema\Field\FieldSchemaProvider\Params;

interface SchemaProvider
{
    /**
     * @todo View type?
     * @return array<string, Schema>
     */
    public function get(Params $params): array;
}
