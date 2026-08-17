<?php
/**LICENSE**/

namespace Espo\Modules\Mcp\Tools\Mcp\Schema\General;

use Espo\Modules\Mcp\Tools\JsonSchema\Schema;
use Espo\Modules\Mcp\Tools\JsonSchema\Type\ObjectType;
use JsonSerializable;
use stdClass;

/**
 * @template TSchema of Schema = Schema
 */
class RootObjectSchema implements JsonSerializable
{
    /**
     * @param ObjectType<TSchema> $schema
     */
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
