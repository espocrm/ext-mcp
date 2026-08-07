<?php
/**LICENSE**/

namespace Espo\Modules\Mcp\Tools\Schema\Field\Types;

use Espo\Core\Currency\ConfigDataProvider;
use Espo\Core\Utils\Language;
use Espo\Modules\Mcp\Tools\JsonSchema\Type\NumberType;
use Espo\Modules\Mcp\Tools\Schema\Field\FieldSchemaProvider\Params;
use Espo\Modules\Mcp\Tools\Schema\Field\FieldSchemaProvider\Result;
use Espo\Modules\Mcp\Tools\Schema\Field\SchemaProvider;
use Espo\Modules\Mcp\Tools\Schema\Field\Util;
use Espo\ORM\Defs;
use Espo\ORM\Defs\Params\FieldParam;

/**
 * @noinspection PhpUnused
 */
class CurrencyConvertedSchemaProvider implements SchemaProvider
{
    public function __construct(
        private Defs $ormDefs,
        private Language $defaultLanguage,
        private ConfigDataProvider $currencyConfig,
    ) {}

    public function get(Params $params): Result
    {
        $fieldDefs = $this->ormDefs->getEntity($params->entityType)->getField($params->field);

        if ($params->isWriteAction()) {
            return new Result();
        }

        $label = $this->defaultLanguage->translateLabel($params->field, 'fields', $params->entityType);

        $field = substr($params->field, -8);

        $currency = $this->currencyConfig->getDefaultCurrency();

        $property = new NumberType(
            title: $label,
            description: "Amount of `$field` field converted to $currency currency."
        );

        if (!$fieldDefs->getParam(FieldParam::REQUIRED)) {
            $property = Util::wrapWithNull($property);
        }

        return new Result(
            properties: [
                $params->field => $property,
            ],
        );
    }
}
