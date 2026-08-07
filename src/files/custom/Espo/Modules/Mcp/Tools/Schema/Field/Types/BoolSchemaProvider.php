<?php
/**LICENSE**/

namespace Espo\Modules\Mcp\Tools\Schema\Field\Types;

use Espo\Core\Utils\Language;
use Espo\Modules\Mcp\Tools\JsonSchema\Type\BooleanType;
use Espo\Modules\Mcp\Tools\Schema\Field\FieldSchemaProvider\Params;
use Espo\Modules\Mcp\Tools\Schema\Field\FieldSchemaProvider\Result;
use Espo\Modules\Mcp\Tools\Schema\Field\SchemaProvider;

/**
 * @noinspection PhpUnused
 */
class BoolSchemaProvider implements SchemaProvider
{
    public function __construct(
        private Language $defaultLanguage,
    ) {}

    public function get(Params $params): Result
    {
        $label = $this->defaultLanguage->translateLabel($params->field, 'fields', $params->entityType);

        return new Result(
            properties: [
                $params->field => new BooleanType(
                    title: $label,
                ),
            ],
        );
    }
}
