<?php
/**LICENSE**/

namespace Espo\Modules\Mcp\Tools\Mcp;

use Espo\Core\Acl;
use Espo\Core\Binding\BindingContainer;
use Espo\Core\Binding\BindingContainerBuilder;
use Espo\Entities\User;
use Espo\Modules\Mcp\Entities\Endpoint;

class BindingProvider
{
    public function __construct(
        private Endpoint $endpoint,
        private User $user,
        private Acl $acl,
    ) {}

    public function get(): BindingContainer
    {
        return BindingContainerBuilder::create()
            ->bindInstance(Endpoint::class, $this->endpoint)
            ->bindInstance(User::class, $this->user)
            ->bindInstance(Acl::class, $this->acl)
            ->build();
    }
}
