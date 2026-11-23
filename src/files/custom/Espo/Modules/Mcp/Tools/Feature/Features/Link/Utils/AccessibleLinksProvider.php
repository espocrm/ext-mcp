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

use Espo\Core\Acl;
use Espo\Core\Utils\Metadata;
use Espo\Modules\Mcp\Tools\Feature\Features\Link\LinkData\Link;
use Espo\ORM\Defs;

/**
 * Filters links to those available for a current user.
 */
class AccessibleLinksProvider
{
    public function __construct(
        private Acl $acl,
        private LinkUtil $linkUtil,
        private Defs $defs,
        private Metadata $metadata,
    ) {}

    /**
     * @param Link[] $links
     * @return Link[]
     */
    public function get(string $entityType, array $links): array
    {
        $forbiddenLinks = $this->acl->getScopeForbiddenLinkList($entityType, Acl\Table::ACTION_EDIT);

        $links = array_filter($links, function (Link $link) use ($entityType, $forbiddenLinks) {
            $name = $link->name;

            if (in_array($name, $forbiddenLinks)) {
                return false;
            }

            if (!$this->acl->tryCheck($entityType, $this->getRequiredEntityAccess($entityType, $name))) {
                return false;
            }

            if (!$this->linkUtil->isLinkable($entityType, $name)) {
                return false;
            }

            $foreignEntityType = $this->getForeignEntityType($entityType, $name);

            if (!$foreignEntityType) {
                return false;
            }

            return $this->acl->tryCheck($foreignEntityType);
        });

        return array_values($links);
    }

    private function getForeignEntityType(string $entityType, string $link): ?string
    {
        return $this->defs
            ->tryGetEntity($entityType)
            ?->tryGetRelation($link)
            ?->tryGetForeignEntityType();
    }

    /**
     * @return Acl\Table::ACTION_*
     */
    private function getRequiredEntityAccess(string $entityType, string $link): string
    {
        return $this->metadata->get("recordDefs.$entityType.relationships.$link.linkRequiredAccess") ??
            Acl\Table::ACTION_EDIT;
    }
}
