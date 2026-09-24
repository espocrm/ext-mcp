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
use Espo\Modules\Mcp\Tools\Feature\Data;
use stdClass;
use UnexpectedValueException;

class Feature extends Entity
{
    public const string ENTITY_TYPE = 'McpFeature';

    public const string FIELD_STATUS = 'status';
    public const string FIELD_TYPE = 'type';
    public const string FIELD_DATA = 'data';
    public const string FIELD_TEXT = 'text';

    public const string LINK_ENDPOINT = 'endpoint';

    public const string STATUS_ACTIVE = 'Active';

    public const string ATTR_ENDPOINT_ID = 'endpointId';

    public function isActive(): bool
    {
        return $this->get(self::FIELD_STATUS) === self::STATUS_ACTIVE;
    }

    public function setType(string $type): self
    {
        return $this->set(self::FIELD_TYPE, $type);
    }

    public function getType(): string
    {
        return $this->get(self::FIELD_TYPE) ?? throw new UnexpectedValueException("No type.");
    }

    public function getRawData(): stdClass
    {
        return $this->get(self::FIELD_DATA) ?? throw new UnexpectedValueException("No data.");
    }

    public function setName(?string $name): self
    {
        return $this->set(Field::NAME, $name);
    }

    public function setData(Data $data): self
    {
        return $this->set(self::FIELD_DATA, $data->jsonSerialize());
    }

    public function setEndpoint(Endpoint $endpoint): self
    {
        return $this->setRelatedLinkOrEntity(self::LINK_ENDPOINT, $endpoint);
    }

    public function getEndpoint(): Endpoint
    {
        $endpoint = $this->relations->getOne(self::LINK_ENDPOINT);

        if (!$endpoint instanceof Endpoint) {
            throw new UnexpectedValueException("No endpoint.");
        }

        return $endpoint;
    }
}
