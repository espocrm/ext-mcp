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

namespace Espo\Modules\Mcp\Tools\Mcp\Schema\Resource;

use Espo\Modules\Mcp\Tools\Mcp\Schema\Elicitation\ElicitResult;
use InvalidArgumentException;
use stdClass;

readonly class ReadResourceRequestParams
{
    /**
     * @param ?array<string, ElicitResult> $inputResponses
     */
    public function __construct(
        public string $uri,
        public ?string $requestState = null,
        public ?array $inputResponses = null,
    ) {}

    public static function fromRaw(stdClass $raw): self
    {
        $uri = $raw->uri ?? null;
        $requestState = $raw->requestState ?? null;
        $inputResponsesRaw = $raw->inputResponses ?? null;

        if (!is_string($uri) || !$uri) {
            throw new InvalidArgumentException("No `uri`.");
        }

        if ($requestState !== null && !is_string($requestState)) {
            throw new InvalidArgumentException("Bad `requestState` type.");
        }

        $inputResponses = null;

        if ($inputResponsesRaw !== null) {
            if (!$inputResponsesRaw instanceof stdClass) {
                throw new InvalidArgumentException("Bad `inputResponses`.");
            }

            $inputResponses = array_map(function ($it) {
                if (!$it instanceof stdClass) {
                    throw new InvalidArgumentException("Bad input response item.");
                }

                $action = $it->action ?? null;

                if ($action) {
                    return ElicitResult::fromRaw($it);
                }

                throw new InvalidArgumentException("Bad input response item.");
            }, get_object_vars($inputResponsesRaw));
        }

        return new self(
            uri: $uri,
            requestState: $requestState,
            inputResponses: $inputResponses,
        );
    }
}
