<?php
/**LICENSE**/

namespace Espo\Modules\Mcp\Tools\Mcp\Tool;

use Espo\Entities\User;
use Espo\Modules\Mcp\Entities\Endpoint;

class CacheKeyProvider
{
    public const string CATEGORY = 'mcp/tools';

    public function __construct(
        private Endpoint $endpoint,
        private User $user,
    ) {}

    public function get(): string
    {
        return self::CATEGORY . '/' . $this->endpoint->getId() . '/' . $this->user->getId();
    }
}
