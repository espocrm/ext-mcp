<?php
/**LICENSE**/

namespace Espo\Modules\Mcp\Tools\Schema\Field\Types;

use Espo\Modules\Mcp\Tools\JsonSchema\Type\IntegerType;
use Espo\Modules\Mcp\Tools\JsonSchema\Type\StringType;
use Espo\Modules\Mcp\Tools\Schema\Field\FieldSchemaProvider\Params;
use Espo\Modules\Mcp\Tools\Schema\Field\FieldSchemaProvider\Result;
use Espo\Modules\Mcp\Tools\Schema\Field\SchemaProvider;
use Espo\ORM\Defs;
use Espo\ORM\Defs\Params\FieldParam;

/**
 * @noinspection PhpUnused
 */
class IdSchemaProvider implements SchemaProvider
{
    private const string DESCRIPTION = "Record ID.";

    public function __construct(
        private Defs $ormDefs,
    ) {}

    public function get(Params $params): Result
    {
        if ($params->isWriteAction()) {
            return new Result();
        }

        $property = new StringType(
            description: self::DESCRIPTION,
        );

        $fieldDefs = $this->ormDefs->getEntity($params->entityType)->tryGetField($params->field);
        $dbType = $fieldDefs?->getParam(FieldParam::DB_TYPE);

        if ($dbType === 'bigint' || $dbType === 'int') {
            $property = new IntegerType(
                description: self::DESCRIPTION,
            );
        }

        return new Result(
            properties: [
                $params->field => $property,
            ],
        );
    }
}
