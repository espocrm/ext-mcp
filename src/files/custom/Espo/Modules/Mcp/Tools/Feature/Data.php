<?php
/**LICENSE**/

namespace Espo\Modules\Mcp\Tools\Feature;

use stdClass;

interface Data
{
    public function composeName(): string;

    public function jsonSerialize(): stdClass;

    public static function fromRaw(stdClass $raw): self;

    public function getKey(): string;
}
