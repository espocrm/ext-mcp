<?php
/**LICENSE**/

namespace Espo\Modules\Mcp\Classes\Acl\McpEndpoint\LinkCheckers;

use Espo\Core\Acl\LinkChecker;
use Espo\Entities\User;
use Espo\Modules\Mcp\Entities\McpEndpoint;
use Espo\ORM\Entity;

/**
 * @implements LinkChecker<McpEndpoint, User>
 */
class Users implements LinkChecker
{
    public function check(User $user, Entity $entity, Entity $foreignEntity): bool
    {
        return $foreignEntity->isApi();
    }
}
