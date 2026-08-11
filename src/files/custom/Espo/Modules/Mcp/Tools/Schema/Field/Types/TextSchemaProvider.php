<?php
/**LICENSE**/

namespace Espo\Modules\Mcp\Tools\Schema\Field\Types;

use Espo\Core\Utils\Language;
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
class TextSchemaProvider implements SchemaProvider
{
    public function __construct(
        private Defs $ormDefs,
        private Language $defaultLanguage,
    ) {}

    public function get(Params $params): Result
    {
        $fieldDefs = $this->ormDefs->getEntity($params->entityType)->getField($params->field);

        $maxLength = null;
        $required = [];

        if ($params->isWriteAction()) {
            $maxLength = $fieldDefs->getParam(FieldParam::MAX_LENGTH);
        }

        if ($fieldDefs->getParam(FieldParam::REQUIRED) && $fieldDefs->getParam(FieldParam::DEFAULT) === null) {
            $required[] = $params->field;
        }

        $description = "Multi-line string.";

        if (!$fieldDefs->getParam('displayRawText')) {
            $description .= " Markdown is supported.";
        }

        $label = $this->defaultLanguage->translateLabel($params->field, 'fields', $params->entityType);

        $property = new StringType(
            maxLength: $maxLength,
            title: $label,
            description: $description,
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
