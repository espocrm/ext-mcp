<?php
/**LICENSE**/

namespace Espo\Modules\Mcp\Tools\Mcp\Schema;

use Espo\Modules\Mcp\Entities\Endpoint;
use Espo\Modules\Mcp\Tools\Mcp\Schema\Meta\Implementation;
use Espo\Modules\Mcp\Tools\Mcp\Schema\Meta\ResultMetaObject;
use JsonSerializable;

class GenericResponseComposer
{
    private const string VERSION = '1.0';
    private const string NAME = 'EspoCRM';

    public function __construct(
        private Endpoint $endpoint,
    ) {}

    public function compose(string|int $id, JsonSerializable $result): GenericResponse
    {
        $meta = new ResultMetaObject(
            serverInfo: new Implementation(
                name: self::NAME . '.' . $this->endpoint->getSlug(),
                version: self::VERSION,
                title: $this->endpoint->getName(),
                description: $this->endpoint->getPublicDescription(),
            )
        );

        return new GenericResponse(
            id: $id,
            result: $result,
            meta: $meta,
        );
    }
}
