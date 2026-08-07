<?php
/**LICENSE**/

namespace Espo\Modules\Mcp\Tools\Schema\Field\Types;

use Espo\Core\Utils\Language;
use Espo\Modules\Mcp\Tools\JsonSchema\Type\IntegerType;
use Espo\Modules\Mcp\Tools\Schema\Field\FieldSchemaProvider\Params;
use Espo\Modules\Mcp\Tools\Schema\Field\FieldSchemaProvider\Result;
use Espo\Modules\Mcp\Tools\Schema\Field\SchemaProvider;
use Espo\Modules\Mcp\Tools\Schema\Field\Util;
use Espo\ORM\Defs;
use Espo\ORM\Defs\Params\FieldParam;

/**
 * @noinspection PhpUnused
 */
class IntSchemaProvider implements SchemaProvider
{
    public function __construct(
        private Defs $ormDefs,
        private Language $defaultLanguage,
    ) {}

    public function get(Params $params): Result
    {
        $fieldDefs = $this->ormDefs->getEntity($params->entityType)->getField($params->field);

        $required = [];

        $min = null;
        $max = null;

        if ($fieldDefs->getParam(FieldParam::REQUIRED)) {
            $required[] = $params->field;
        }

        if ($params->isWriteAction()) {
            $min = $fieldDefs->getParam(FieldParam::MIN);
            $max = $fieldDefs->getParam(FieldParam::MAX);
        }

        $label = $this->defaultLanguage->translateLabel($params->field, 'fields', $params->entityType);

        $property = new IntegerType(
            min: $min,
            max: $max,
            title: $label,
        );

        if (!$fieldDefs->getParam(FieldParam::REQUIRED)) {
            $property = Util::wrapWithNull($property);
        }

        return new Result(
            properties: [
                $params->field => $property,
            ],
            required: $required,
        );
    }
}
