<?php
/**LICENSE**/

namespace Espo\Modules\Mcp\Classes\Acl\Endpoint\LinkCheckers;

use Espo\Core\Acl\LinkChecker;
use Espo\Entities\User;
use Espo\Modules\Mcp\Entities\Endpoint;
use Espo\ORM\Entity;

/**
 * @implements LinkChecker<Endpoint, User>
 */
class Users implements LinkChecker
{
    public function check(User $user, Entity $entity, Entity $foreignEntity): bool
    {
        return $foreignEntity->isApi();
    }
}
