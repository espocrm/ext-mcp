<?php
/**LICENSE**/

namespace Espo\Modules\Mcp\Tools\Mcp;

use Espo\Core\Acl;
use Espo\Core\Binding\BindingContainer;
use Espo\Core\Binding\BindingContainerBuilder;
use Espo\Entities\User;
use Espo\Modules\Mcp\Entities\Endpoint;

class BindingPreparator
{
    public function __construct(
        private User $user,
        private Acl $acl,
    ) {}

    public function prepare(Endpoint $endpoint): BindingContainer
    {
        return BindingContainerBuilder::create()
            ->bindInstance(Endpoint::class, $endpoint)
            ->bindInstance(User::class, $this->user)
            ->bindInstance(Acl::class, $this->acl)
            ->build();
    }
}
