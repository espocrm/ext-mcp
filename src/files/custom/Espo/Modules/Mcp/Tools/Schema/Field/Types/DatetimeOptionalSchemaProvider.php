<?php
/**LICENSE**/

namespace Espo\Modules\Mcp\Tools\Schema\Field\Types;

use Espo\Core\Utils\Language;
use Espo\Modules\Mcp\Tools\JsonSchema\Schema;
use Espo\Modules\Mcp\Tools\JsonSchema\StringFormat;
use Espo\Modules\Mcp\Tools\JsonSchema\Type\StringType;
use Espo\Modules\Mcp\Tools\Schema\Field\FieldSchemaProvider\Params;
use Espo\Modules\Mcp\Tools\Schema\Field\FieldSchemaProvider\Result;
use Espo\Modules\Mcp\Tools\Schema\Field\SchemaProvider;
use Espo\Modules\Mcp\Tools\Schema\Field\Util;

/**
 * @noinspection PhpUnused
 */
class DatetimeOptionalSchemaProvider implements SchemaProvider
{
    public function __construct(
        private Language $defaultLanguage,
        private DatetimeSchemaProvider $datetimeSchemaProvider,
    ) {}

    public function get(Params $params): Result
    {
        $dateField = $this->composeDateFieldName($params);

        $result = $this->datetimeSchemaProvider->get($params);

        return new Result(
            properties: [
                ...$result->properties,
                $dateField => $this->getDateSchema($params),
            ],
            required: $result->required,
            suppress: [$dateField],
        );
    }

    private function getDateSchema(Params $params): Schema
    {
        $dateField = $this->composeDateFieldName($params);

        $label = $this->defaultLanguage->translateLabel($dateField, 'fields', $params->entityType);

        $description = "Is set only when `$params->field` represents all-day (the time part is omitted). " .
            "Should be `null` otherwise.";

        $property = new StringType(
            format: StringFormat::date,
            title: $label,
            description: $description,
        );

        return Util::wrapWithNull($property);
    }

    private function composeDateFieldName(Params $params): string
    {
        return $params->field . 'Date';
    }
}
