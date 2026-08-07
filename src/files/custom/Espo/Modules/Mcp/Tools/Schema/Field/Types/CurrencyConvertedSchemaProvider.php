<?php
/**LICENSE**/

namespace Espo\Modules\Mcp\Tools\Schema\Field\Types;

use Espo\Core\Currency\ConfigDataProvider;
use Espo\Core\Utils\Language;
use Espo\Modules\Mcp\Tools\JsonSchema\Type\NumberType;
use Espo\Modules\Mcp\Tools\Schema\Field\FieldSchemaProvider\Params;
use Espo\Modules\Mcp\Tools\Schema\Field\FieldSchemaProvider\Result;
use Espo\Modules\Mcp\Tools\Schema\Field\SchemaProvider;

/**
 * @noinspection PhpUnused
 */
class CurrencyConvertedSchemaProvider implements SchemaProvider
{
    public function __construct(
        private Language $defaultLanguage,
        private ConfigDataProvider $currencyConfig,
    ) {}

    public function get(Params $params): Result
    {
        if ($params->isWriteAction()) {
            return new Result();
        }

        $label = $this->defaultLanguage->translateLabel($params->field, 'fields', $params->entityType);

        $field = substr($params->field, -8);

        $currency = $this->currencyConfig->getDefaultCurrency();

        return new Result(
            properties: [
                $params->field => new NumberType(
                    title: $label,
                    description: "Amount of `$field` field converted to $currency currency."
                ),
            ],
        );
    }
}
