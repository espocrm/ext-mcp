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

namespace Espo\Modules\Mcp\Tools\Feature\Features\Link;

use Espo\Modules\Mcp\Tools\Feature\Data;
use Espo\Modules\Mcp\Tools\Feature\DataUtil;
use Espo\Modules\Mcp\Tools\Feature\Features\Link\LinkData\Link;
use InvalidArgumentException;
use stdClass;

readonly class LinkData implements Data
{
    use Data\Traits\GetKeyTrait;

    public const string TYPE = 'Link';

    /**
     * @param Link[] $links
     */
    final public function __construct(
        public string $entityType,
        public array $links,
    ) {}

    public function composeName(): string
    {
        return $this->getKey();
    }

    public function jsonSerialize(): stdClass
    {
        return (object) [
            'entityType' => $this->entityType,
            'links' => array_map(fn ($it) => (object) get_object_vars($it), $this->links),
        ];
    }

    public static function fromRaw(stdClass $raw): static
    {
        $entityType = $raw->entityType ?? null;
        $links = $raw->links ?? null;

        if (!is_string($entityType)) {
            throw new InvalidArgumentException("No 'entityType'.");
        }

        DataUtil::assertArrayOfFields($links, 'links', true);

        return new static(
            entityType: $entityType,
            links: array_map(function ($it) {
                return new Link(
                    name: $it->name,
                    description: $it->description ?? null,
                );
            }, $links),
        );
    }
}
