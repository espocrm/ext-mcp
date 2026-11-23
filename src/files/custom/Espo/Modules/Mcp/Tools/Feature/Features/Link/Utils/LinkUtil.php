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

namespace Espo\Modules\Mcp\Tools\Feature\Features\Link\Utils;

use Espo\Core\Acl\SystemRestriction;
use Espo\Core\Utils\Metadata;
use Espo\ORM\Defs\Params\RelationParam;
use Espo\ORM\Type\RelationType;

class LinkUtil
{
    private const array LINK_TYPES = [
        RelationType::HAS_MANY,
        RelationType::HAS_CHILDREN,
    ];

    public function __construct(
        private Metadata $metadata,
        private SystemRestriction $systemRestriction,
    ) {}

    public function isLinkable(string $entityType, string $link): bool
    {
        /** @var ?array<string, mixed> $defs */
        $defs = $this->metadata->get("entityDefs.$entityType.links.$link");

        if (!$defs) {
            return false;
        }

        $type = $defs['type'] ?? null;

        if (
            !in_array($type, self::LINK_TYPES) ||
            ($defs[RelationParam::DISABLED] ?? false) ||
            ($defs[RelationParam::READ_ONLY] ?? false)
        ) {
            return false;
        }

        if (!$this->systemRestriction->checkLinkWrite($entityType, $link)) {
            return false;
        }

        $foreignEntityType = $this->getForeignEntityType($entityType, $link);

        if (
            !$foreignEntityType ||
            !$this->metadata->get("scopes.$foreignEntityType.entity") ||
            $this->metadata->get("scopes.$foreignEntityType.disabled")
        ) {
            return false;
        }

        return true;
    }

    private function getForeignEntityType(string $entityType, string $link): ?string
    {
        /** @var ?string */
        return $this->metadata->get("entityDefs.$entityType.links.$link.entity");
    }
}
