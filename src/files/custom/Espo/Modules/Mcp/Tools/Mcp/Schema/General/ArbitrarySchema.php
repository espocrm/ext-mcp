<?php
/**LICENSE**/

namespace Espo\Modules\Mcp\Tools\Mcp\Schema\General;

use Espo\Modules\Mcp\Tools\JsonSchema\Schema;
use JsonSerializable;
use stdClass;

class ArbitrarySchema implements JsonSerializable
{
    public const string SCHEMA = 'https://json-schema.org/draft/2020-12/schema';

    public function __construct(
        public Schema $schema,
    ) {}

    public function jsonSerialize(): stdClass
    {
        return (object) [
            '$schema' => ArbitrarySchema::SCHEMA,
            ...get_object_vars($this->schema->jsonSerialize()),
        ];
    }
}
