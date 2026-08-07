<?php
/**LICENSE**/

namespace Espo\Modules\Mcp\Tools\Schema\Field\Types;

use Espo\Core\Utils\Language;
use Espo\Modules\Mcp\Tools\JsonSchema\Type\StringType;
use Espo\Modules\Mcp\Tools\Schema\Field\FieldSchemaProvider\Params;
use Espo\Modules\Mcp\Tools\Schema\Field\FieldSchemaProvider\Result;
use Espo\Modules\Mcp\Tools\Schema\Field\SchemaProvider;

/**
 * @noinspection PhpUnused
 */
class PersonNameSchemaProvider implements SchemaProvider
{
    public function __construct(
        private Language $defaultLanguage,
    ) {}

    public function get(Params $params): Result
    {
        $label = $this->defaultLanguage->translateLabel($params->field, 'fields', $params->entityType);

        $property = new StringType(
            title: $label,
            description: "Person name.",
        );

        return new Result(
            properties: [
                $params->field => $property,
            ],
        );
    }
}
