<?php
/**LICENSE**/

namespace Espo\Modules\Mcp\Tools\Mcp\Schema\General;

use Espo\Modules\Mcp\Tools\JsonSchema\Type\ObjectType;
use JsonSerializable;
use stdClass;

class RootObjectSchema implements JsonSerializable
{
    public function __construct(
        public ObjectType $schema,
    ) {}

    public function jsonSerialize(): stdClass
    {
        return (object) [
            'type' => 'object',
            '$schema' => RootSchema::SCHEMA,
            ...get_object_vars($this->schema->jsonSerialize()),
        ];
    }
}
