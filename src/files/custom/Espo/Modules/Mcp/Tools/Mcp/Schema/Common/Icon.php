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

namespace Espo\Modules\Mcp\Tools\Mcp\Schema\Common;

use JsonSerializable;
use stdClass;

class Icon implements JsonSerializable
{
    /**
     * @param string[] $sizes
     */
    public function __construct(
        public string $src,
        public ?string $mimeType = null,
        public ?array $sizes = null,
        public ?Theme $theme = null,
    ) {}

    public function jsonSerialize(): stdClass
    {
        $object = (object) [
            'src' => $this->src,
            'mimeType' => $this->mimeType,
            'sizes' => $this->sizes,
            'theme' => $this->theme?->value,
        ];

        if ($this->mimeType === null) {
            unset($object->mimeType);
        }

        if ($this->sizes === null) {
            unset($object->sizes);
        }

        if ($this->theme === null) {
            unset($object->theme);
        }

        return $object;
    }
}
