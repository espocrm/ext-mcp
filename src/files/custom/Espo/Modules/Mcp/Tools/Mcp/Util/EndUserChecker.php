<?php
/**LICENSE**/

namespace Espo\Modules\Mcp\Tools\Mcp\Util;

use Espo\Entities\User;

class EndUserChecker
{
    /**
     * Checks whether the user is eligible for MCP usage.
     */
    public function check(User $user): bool
    {
        return in_array($user->getType(), [
            User::TYPE_API,
            User::TYPE_ADMIN,
            User::TYPE_REGULAR,
        ]);
    }
}
