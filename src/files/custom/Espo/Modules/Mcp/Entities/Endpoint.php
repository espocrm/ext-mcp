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

namespace Espo\Modules\Mcp\Entities;

use Espo\Core\Name\Field;
use Espo\Core\ORM\Entity;
use Espo\ORM\EntityCollection;
use UnexpectedValueException;

class Endpoint extends Entity
{
    public const string ENTITY_TYPE = 'McpEndpoint';

    public const string FIELD_STATUS = 'status';
    public const string FIELD_SLUG = 'slug';
    public const string FIELD_URL = 'url';
    public const string FIELD_PUBLIC_DESCRIPTION = 'publicDescription';

    public const string LINK_USERS = 'users';
    private const string LINK_FEATURES = 'features';

    public const string STATUS_ACTIVE = 'Active';

    public function isActive(): bool
    {
        return $this->get(self::FIELD_STATUS) === self::STATUS_ACTIVE;
    }

    public function getSlug(): string
    {
        return $this->get(self::FIELD_SLUG) ?? throw new UnexpectedValueException("No slug.");
    }

    public function setSlug(string $slug): self
    {
        return $this->set(self::FIELD_SLUG, $slug);
    }

    public function getPublicDescription(): ?string
    {
        return $this->get(self::FIELD_PUBLIC_DESCRIPTION);
    }

    public function setPublicDescription(?string $description): self
    {
        return $this->set(self::FIELD_PUBLIC_DESCRIPTION, $description);
    }

    public function getName(): string
    {
        return $this->get(Field::NAME) ?? '';
    }

    public function setName(string $name): self
    {
        return $this->set(Field::NAME, $name);
    }

    /**
     * @return EntityCollection<Feature>
     */
    public function getFeatures(): EntityCollection
    {
        /** @var EntityCollection<Feature> */
        return $this->relations->getMany(self::LINK_FEATURES);
    }
}
