<?php
/**LICENSE**/

namespace Espo\Modules\Mcp\Tools\Schema\Field;

use Espo\Modules\Mcp\Tools\JsonSchema\GroupSchema;
use Espo\Modules\Mcp\Tools\JsonSchema\Schema;
use Espo\Modules\Mcp\Tools\JsonSchema\Type\NullType;

class Util
{
    public static function wrapWithNull(Schema $schema): Schema
    {
        $title = $schema->getTitle();
        $description = $schema->getDescription();

        return GroupSchema::createAnyOf(
            schemas: [
                $schema
                    ->withDescription(null)
                    ->withTitle(null),
                new NullType(),
            ],
            title: $title,
            description: $description,
        );
    }
}
