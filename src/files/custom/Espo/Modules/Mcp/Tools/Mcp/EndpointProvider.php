<?php
/************************************************************************
* This file is part of MCP extension for EspoCRM.
*
* MCP extension for EspoCRM.
* Copyright (C) 2026 EspoCRM, Inc.
* Website: https://www.espocrm.com
*
* This program is free software: you can redistribute it and/or modify
* it under the terms of the GNU Affero General Public License as published by
* the Free Software Foundation, either version 3 of the License, or
* (at your option) any later version.
*
* This program is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
* GNU Affero General Public License for more details.
*
* You should have received a copy of the GNU Affero General Public License
* along with this program. If not, see <https://www.gnu.org/licenses/>.
*
* The interactive user interfaces in modified source and object code versions
* of this program must display Appropriate Legal Notices, as required under
* Section 5 of the GNU Affero General Public License version 3.
*
* In accordance with Section 7(b) of the GNU Affero General Public License version 3,
* these Appropriate Legal Notices must retain the display of the "EspoCRM" word.
************************************************************************/

namespace Espo\Modules\Mcp\Tools\Mcp;

use Espo\Core\Acl;
use Espo\Core\Exceptions\Forbidden;
use Espo\Core\Exceptions\NotFound;
use Espo\Entities\User;
use Espo\Modules\Mcp\Entities\Endpoint;
use Espo\ORM\EntityManager;

class EndpointProvider
{
    private const string SCOPE = Scope::MCP;

    public function __construct(
        private EntityManager $entityManager,
        private User $user,
        private Acl $acl,
    ) {}

    /**
     * @throws NotFound
     * @throws Forbidden
     */
    public function get(string $slug): Endpoint
    {
        if (!$this->acl->checkScope(self::SCOPE)) {
            throw new Forbidden("No access to 'Mcp' scope.");
        }

        $endpoint = $this->entityManager
            ->getRDBRepositoryByClass(Endpoint::class)
            ->where([
                Endpoint::FIELD_SLUG => $slug,
            ])
            ->findOne();

        if (!$endpoint) {
            throw new NotFound("MCP endpoint '$slug' not found.");
        }

        if (!$endpoint->isActive()) {
            throw new Forbidden("MCP endpoint '$slug' is not active.");
        }

        $related = $this->entityManager
            ->getRelation($endpoint, Endpoint::LINK_USERS)
            ->isRelated($this->user);

        if (!$related) {
            throw new Forbidden("User is not associated with MCP endpoint '$slug'.");
        }

        return $endpoint;
    }
}
