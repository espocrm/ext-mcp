<?php
/**LICENSE**/

namespace Espo\Modules\Mcp\Tools\Schema\Field\Types;

use Espo\Core\Utils\Language;
use Espo\Modules\Mcp\Tools\JsonSchema\StringFormat;
use Espo\Modules\Mcp\Tools\JsonSchema\Type\StringType;
use Espo\Modules\Mcp\Tools\Schema\Field\FieldSchemaProvider\Params;
use Espo\Modules\Mcp\Tools\Schema\Field\FieldSchemaProvider\Result;
use Espo\Modules\Mcp\Tools\Schema\Field\SchemaProvider;
use Espo\ORM\Defs;
use Espo\ORM\Defs\Params\FieldParam;

/**
 * @noinspection PhpUnused
 */
class UrlSchemaProvider implements SchemaProvider
{
    private const int MAX_LENGTH = 255;

    public function __construct(
        private Defs $ormDefs,
        private Language $defaultLanguage,
    ) {}

    public function get(Params $params): Result
    {
        $fieldDefs = $this->ormDefs->getEntity($params->entityType)->getField($params->field);

        $maxLength = null;

        if ($params->isWriteAction()) {
            $maxLength = $fieldDefs->getParam(FieldParam::MAX_LENGTH) ?? self::MAX_LENGTH;
        }

        $required = [];

        if ($fieldDefs->getParam(FieldParam::REQUIRED)) {
            $required[] = $params->field;
        }

        $label = $this->defaultLanguage->translateLabel($params->field, 'fields', $params->entityType);

        $property = new StringType(
            maxLength: $maxLength,
            format: $fieldDefs->getParam('protocolRequired') ? StringFormat::uri : null,
            title: $label,
        );

        return new Result(
            properties: [
                $params->field => $property,
            ],
            required: $required,
        );
    }
}
