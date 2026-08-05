<?php
/**LICENSE**/

namespace Espo\Modules\Mcp\Tools\JsonSchema;

use Espo\Modules\Mcp\Tools\JsonSchema\Group\Keyword;
use stdClass;

class Group extends Item
{
    public function __construct(
        private Keyword $keyword,
        private $schemas =
    ) {}

    public function jsonSerialize(): stdClass
    {
        // TODO: Implement jsonSerialize() method.
    }
}
