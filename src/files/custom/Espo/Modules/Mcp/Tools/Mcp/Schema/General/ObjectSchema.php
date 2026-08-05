<?php
/**LICENSE**/

namespace Espo\Modules\Mcp\Tools\Mcp\Schema\General;

use Espo\Modules\Mcp\Tools\JsonSchema\Type\ObjectType;
use JsonSerializable;
use stdClass;

class ObjectSchema implements JsonSerializable
{
    public function __construct(
        public ObjectType $data,
    ) {}

    public function jsonSerialize(): stdClass
    {
        return (object) [
            'type' => 'object',
            '$schema' => ArbitrarySchema::SCHEMA,
            ...get_object_vars($this->data),
        ];
    }
}
