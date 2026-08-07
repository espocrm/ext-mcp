<?php
/**LICENSE**/

namespace Espo\Modules\Mcp\Tools\Schema\Field\Types;

use Espo\Core\Currency\ConfigDataProvider;
use Espo\Core\Utils\Language;
use Espo\Modules\Mcp\Tools\JsonSchema\EnumSchema;
use Espo\Modules\Mcp\Tools\JsonSchema\Type\NumberType;
use Espo\Modules\Mcp\Tools\JsonSchema\Type\StringType;
use Espo\Modules\Mcp\Tools\Schema\Field\FieldSchemaProvider\Params;
use Espo\Modules\Mcp\Tools\Schema\Field\FieldSchemaProvider\Result;
use Espo\Modules\Mcp\Tools\Schema\Field\SchemaProvider;
use Espo\Modules\Mcp\Tools\Schema\Field\Util;
use Espo\ORM\Defs;
use Espo\ORM\Defs\Params\FieldParam;

/**
 * @noinspection PhpUnused
 */
class CurrencySchemaProvider implements SchemaProvider
{
    public function __construct(
        private Defs $ormDefs,
        private Language $defaultLanguage,
        private ConfigDataProvider $currencyConfig,
    ) {}

    public function get(Params $params): Result
    {
        $fieldDefs = $this->ormDefs->getEntity($params->entityType)->getField($params->field);

        $field = $params->field;
        $codeField = $params->field . 'Currency';

        $min = null;
        $max = null;

        if ($params->isWriteAction()) {
            $min = $fieldDefs->getParam(FieldParam::MIN);
            $max = $fieldDefs->getParam(FieldParam::MAX);
        }

        $required = [];

        if ($fieldDefs->getParam(FieldParam::REQUIRED) && $fieldDefs->getParam(FieldParam::DEFAULT) === null) {
            $required[] = $params->field;
            $required[] = $codeField;
        }

        $label = $this->defaultLanguage->translateLabel($params->field, 'fields', $params->entityType);

        $codeList = $this->currencyConfig->getCurrencyList();
        $defaultCode = $this->currencyConfig->getDefaultCurrency();

        $description = "Amount. Currency code is set in `$codeField` field.";

        $property = new NumberType(
            min: $min,
            max: $max,
            title: $label,
            description: $description,
        );

        if ($fieldDefs->getParam('decimal')) {
            if ($min !== null) {
                $description .= " Min value: `$min`.";
            }

            if ($max !== null) {
                $description .= " Max value: `$max`.";
            }

            $property = new StringType(
                pattern: "^-?\\d+(?:\\.\\d+)?$",
                title: $label,
                description: $description,
            );
        }

        $codeProperty = new EnumSchema(
            values: $codeList,
            description: "Currency code for `$field` field.",
        );

        $codeProperty = $codeProperty->withDefault($defaultCode);

        if (!$fieldDefs->getParam(FieldParam::REQUIRED)) {
            $property = Util::wrapWithNull($property);
            $codeProperty = Util::wrapWithNull($codeProperty);
        }

        return new Result(
            properties: [
                $params->field => $property,
                $codeField => $codeProperty,
            ],
            required: $required,
            suppress: [$codeField],
        );
    }
}
