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

namespace Espo\Modules\Mcp\Tools\Mcp\Schema\Elicitation;

use InvalidArgumentException;
use JsonSerializable;
use stdClass;

readonly class ElicitResult implements JsonSerializable
{
    /**
     * @param ?array<string, string|int|float|bool|string[]> $content
     */
    public function __construct(
        public ElicitAction $action,
        public ?array $content = null,
    ) {}

    public static function fromRaw(mixed $raw): self
    {
        if (!$raw instanceof stdClass) {
            throw new InvalidArgumentException("Bad elicit result.");
        }

        $rawAction = $raw->action ?? null;

        if (!$rawAction) {
            throw new InvalidArgumentException("Bad elicit result action");
        }

        $action = ElicitAction::from($rawAction);

        $content = null;

        $rawContent = $raw->content ?? null;

        if ($rawContent !== null) {
            if (!$rawContent instanceof stdClass) {
                throw new InvalidArgumentException("Bad elicit result content.");
            }

            $content = get_object_vars($rawContent);

            foreach ($content as $item) {
                if (is_bool($item) || is_string($item) || is_float($item) || is_int($item)) {
                    continue;
                }

                if (!is_array($item)) {
                    throw new InvalidArgumentException("Bad elicit content item value.");
                }

                foreach ($item as $subItem) {
                    if (!is_string($subItem)) {
                        throw new InvalidArgumentException("Bad elicit content array item value.");
                    }
                }
            }
        }

        return new self(
            action: $action,
            content: $content,
        );
    }

    public function jsonSerialize(): stdClass
    {
        $object = (object) [
            'action' => $this->action->value,
        ];

        if ($this->content) {
            $object->content = (object) $this->content;
        }

        return $object;
    }
}
