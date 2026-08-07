<?php
/**LICENSE**/

namespace Espo\Modules\Mcp\Tools\Schema\Field\Types;

use Espo\Modules\Mcp\Tools\Schema\Field\FieldSchemaProvider\Params;
use Espo\Modules\Mcp\Tools\Schema\Field\FieldSchemaProvider\Result;
use Espo\Modules\Mcp\Tools\Schema\Field\SchemaProvider;

/**
 * @noinspection PhpUnused
 */
class NumberSchemaProvider implements SchemaProvider
{
    public function __construct(
        private VarcharSchemaProvider $varcharSchemaProvider,
    ) {}

    public function get(Params $params): Result
    {
        if ($params->isWriteAction()) {
            return new Result();
        }

        return $this->varcharSchemaProvider->get($params);
    }
}
