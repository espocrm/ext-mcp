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

namespace Espo\Modules\Mcp\Tools\Mcp\Resource;

use Espo\Core\InjectableFactory;
use Espo\Modules\Mcp\Tools\Mcp\BindingProvider;
use Espo\Modules\Mcp\Tools\Mcp\Exceptions\Espo\NoAccessError;
use Espo\Modules\Mcp\Tools\Mcp\Exceptions\Espo\TooLargeError;
use Espo\Modules\Mcp\Tools\Mcp\Exceptions\InternalError;
use Espo\Modules\Mcp\Tools\Mcp\Exceptions\InvalidParamsError;
use Espo\Modules\Mcp\Tools\Mcp\ResourceTemplate\MetadataProvider;
use Espo\Modules\Mcp\Tools\Mcp\Schema\Resource\ReadResourceRequestParams;
use Espo\Modules\Mcp\Tools\Mcp\Schema\Resource\ReadResourceResult;
use Espo\Modules\Mcp\Tools\ResourceTemplate\Params;
use Espo\Modules\Mcp\Tools\ResourceTemplate\Reader;
use Espo\Modules\Mcp\Vendor\Rize\UriTemplate;
use InvalidArgumentException;
use LogicException;

/**
 * The spec defines that the resource read can return `InputRequiredResult`. Not implemented.
 */
class ResourcesReadProcessor
{
    public function __construct(
        private MetadataProvider $metadataProvider,
        private BindingProvider $bindingProvider,
        private InjectableFactory $injectableFactory,
    ) {}

    /**
     * @throws InvalidParamsError
     * @throws InternalError
     * @throws TooLargeError
     * @throws NoAccessError
     */
    public function process(ReadResourceRequestParams $params): ReadResourceResult
    {
        $uri = $params->uri;

        foreach (array_keys($this->metadataProvider->getAll()) as $type) {
            $result = $this->processItem($type, $uri);

            if (!$result) {
                continue;
            }

            return $result;
        }

        throw InvalidParamsError::create("Resource not found.", ['uri' => $uri]);
    }

    /**
     * @throws InternalError
     * @throws InvalidParamsError
     * @throws NoAccessError
     * @throws TooLargeError
     */
    private function processItem(string $type, string $uri): ?ReadResourceResult
    {
        $defs = $this->metadataProvider->getAll()[$type] ?? throw new LogicException();

        $parsed = (new UriTemplate())->extract($defs['uriTemplate'], $uri, true);

        if ($parsed === null) {
            return null;
        }

        /** @var class-string<Reader<Params>> $readerClassName */
        $readerClassName = $defs['readerClassName'];
        /** @var class-string<Params> $paramsClassName */
        $paramsClassName = $defs['paramsClassName'];

        try {
            $params = $paramsClassName::create($uri, $parsed);
        } catch (InvalidArgumentException $e) {
            throw new InternalError(previous: $e);
        }

        $reader = $this->injectableFactory->createWithBinding($readerClassName, $this->bindingProvider->get());

        if (!$reader instanceof Reader) {
            throw new LogicException("Bad resource template reader.");
        }

        return $reader->read($params);
    }
}
