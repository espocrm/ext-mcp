<?php
/**LICENSE**/

namespace Espo\Modules\Mcp\Tools\Mcp\Schema\Elicitation;

use JsonSerializable;
use stdClass;

readonly class ElicitRequest implements JsonSerializable
{
    public function __construct(
        public ElicitRequestFormParams $params,
    ) {}

    public function jsonSerialize(): stdClass
    {
        return (object) [
            'method' => 'elicitation/create',
            'params' => $this->params->jsonSerialize(),
        ];
    }
}
