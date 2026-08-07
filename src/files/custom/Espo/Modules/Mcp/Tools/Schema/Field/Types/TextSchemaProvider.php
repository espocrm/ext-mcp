<?php
/**LICENSE**/

namespace Espo\Modules\Mcp\Tools\Schema\Field\Types;

use Espo\Core\Utils\Language;
use Espo\Modules\Mcp\Tools\JsonSchema\Type\StringType;
use Espo\Modules\Mcp\Tools\Schema\Field\FieldSchemaProvider\Params;
use Espo\Modules\Mcp\Tools\Schema\Field\FieldSchemaProvider\Result;
use Espo\Modules\Mcp\Tools\Schema\Field\SchemaProvider;
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

        if ($fieldDefs->getParam(FieldParam::REQUIRED)) {
            $required[] = $params->field;
        }

        $description = "Multi-line string.";

        if (!$fieldDefs->getParam('displayRawText')) {
            $description .= " Markdown supported.";
        }

        $label = $this->defaultLanguage->translateLabel($params->field, 'fields', $params->entityType);

        return new Result(
            properties: [
                $params->field => new StringType(
                    maxLength: $maxLength,
                    title: $label,
                    description: $description,
                ),
            ],
            required: $required,
        );
    }
}
