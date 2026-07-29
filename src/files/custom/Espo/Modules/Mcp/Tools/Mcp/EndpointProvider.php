<?php
/**LICENSE**/

namespace Espo\Modules\Mcp\Tools\Mcp;

use Espo\Core\Acl;
use Espo\Core\Exceptions\Forbidden;
use Espo\Core\Exceptions\NotFound;
use Espo\Entities\User;
use Espo\Modules\Mcp\Entities\McpEndpoint;
use Espo\ORM\EntityManager;

class EndpointProvider
{
    private const string SCOPE = 'Mcp';

    public function __construct(
        private EntityManager $entityManager,
        private User $user,
        private Acl $acl,
    ) {}

    /**
     * @throws NotFound
     * @throws Forbidden
     */
    public function get(string $slug): McpEndpoint
    {
        if (!$this->acl->checkScope(self::SCOPE)) {
            throw new Forbidden("No access to 'Mcp' scope.");
        }

        $endpoint = $this->entityManager
            ->getRDBRepositoryByClass(McpEndpoint::class)
            ->where([
                McpEndpoint::FIELD_SLUG => $slug,
            ])
            ->findOne();

        if (!$endpoint) {
            throw new NotFound("MCP endpoint '$slug' not found.");
        }

        if (!$endpoint->isActive()) {
            throw new Forbidden("MCP endpoint '$slug' is not active.");
        }

        $related = $this->entityManager
            ->getRelation($endpoint, McpEndpoint::LINK_USERS)
            ->isRelated($this->user);

        if (!$related) {
            throw new Forbidden("User is not associated with MCP endpoint '$slug'.");
        }

        return $endpoint;
    }
}
