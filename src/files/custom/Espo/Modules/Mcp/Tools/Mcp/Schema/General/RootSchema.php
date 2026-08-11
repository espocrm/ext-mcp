<?php
/**LICENSE**/

namespace Espo\Modules\Mcp\Tools\Mcp\Schema\General;

use Espo\Modules\Mcp\Tools\JsonSchema\Schema;
use JsonSerializable;
use stdClass;

class RootSchema implements JsonSerializable
{
    public const string SCHEMA = 'https://json-schema.org/draft/2020-12/schema';

    public function __construct(
        public Schema $schema,
    ) {}

    public function jsonSerialize(): stdClass
    {
        return (object) [
            '$schema' => RootSchema::SCHEMA,
            ...get_object_vars($this->schema->jsonSerialize()),
        ];
    }

    public function getSchema(): Schema
    {
        return $this->schema;
    }
}
