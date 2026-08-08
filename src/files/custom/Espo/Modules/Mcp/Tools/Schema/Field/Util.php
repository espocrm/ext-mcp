<?php
/**LICENSE**/

namespace Espo\Modules\Mcp\Tools\Schema\Field;

use Espo\Modules\Mcp\Tools\JsonSchema\ConstSchema;
use Espo\Modules\Mcp\Tools\JsonSchema\GroupSchema;
use Espo\Modules\Mcp\Tools\JsonSchema\Schema;
use Espo\Modules\Mcp\Tools\JsonSchema\Type\NullType;
use Espo\Modules\Mcp\Tools\JsonSchema\Type\Type;
use Espo\Modules\Mcp\Tools\JsonSchema\UnionTypeSchema;

class Util
{
    public static function wrapWithNull(Schema $schema): Schema
    {
        if ($schema instanceof Type) {
            return new UnionTypeSchema(
                schemas: [
                    $schema,
                    new NullType(),
                ],
            );
        }

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
