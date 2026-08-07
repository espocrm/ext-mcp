<?php
/**LICENSE**/

namespace Espo\Modules\Mcp\Tools\Schema\Field\Types;

use Espo\Core\Utils\Language;
use Espo\Modules\Mcp\Tools\JsonSchema\Type\IntegerType;
use Espo\Modules\Mcp\Tools\Schema\Field\FieldSchemaProvider\Params;
use Espo\Modules\Mcp\Tools\Schema\Field\FieldSchemaProvider\Result;
use Espo\Modules\Mcp\Tools\Schema\Field\SchemaProvider;

/**
 * @noinspection PhpUnused
 */
class AutoincrementSchemaProvider implements SchemaProvider
{
    public function __construct(
        private Language $defaultLanguage,
    ) {}

    public function get(Params $params): Result
    {
        $label = $this->defaultLanguage->translateLabel($params->field, 'fields', $params->entityType);

        return new Result(
            properties: [
                $params->field => new IntegerType(
                    title: $label,
                    description: "Autoincrement number.",
                ),
            ],
        );
    }
}
