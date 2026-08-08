<?php
/**LICENSE**/

namespace Espo\Modules\Mcp\Tools\Schema\Field;

use Espo\Modules\Mcp\Tools\JsonSchema\ConstSchema;
use Espo\Modules\Mcp\Tools\JsonSchema\GroupSchema;
use Espo\Modules\Mcp\Tools\JsonSchema\Schema;

/**
 * @todo Consider a nullable type instead.
 */
class Util
{
    public static function wrapWithNull(Schema $schema): GroupSchema
    {
        $title = $schema->getTitle();
        $description = $schema->getDescription();

        return GroupSchema::createAnyOf(
            schemas: [
                $schema
                    ->withDescription(null)
                    ->withTitle(null),
                new ConstSchema(value: null),
            ],
            title: $title,
            description: $description,
        );
    }
}
