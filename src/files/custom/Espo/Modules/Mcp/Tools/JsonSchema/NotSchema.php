<?php
/**LICENSE**/

namespace Espo\Modules\Mcp\Tools\JsonSchema;

use Espo\Modules\Mcp\Tools\Schema\Field\Traits\CommonTrait;
use stdClass;

class NotSchema implements Schema
{
    use CommonTrait;

    public function __construct(
        private Schema $schema,
        private ?string $title = null,
        private ?string $description = null,
    ) {}

    public function jsonSerialize(): stdClass
    {
        $object = (object) [
           'not' => $this->schema->jsonSerialize(),
        ];

        if ($this->title !== null) {
            $object->title = $this->title;
        }

        if ($this->description !== null) {
            $object->description = $this->description;
        }

        return $object;
    }

    public function getSchema(): Schema
    {
        return $this->schema;
    }
}
