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

use Espo\Core\Acl;
use Espo\Core\Utils\Language;
use Espo\Core\Utils\Metadata;
use Espo\Modules\Mcp\Tools\Feature\Data;
use Espo\Modules\Mcp\Tools\Feature\Exceptions\NoUserAccess;
use Espo\Modules\Mcp\Tools\Feature\Features\Link\LinkData\Link;
use Espo\Modules\Mcp\Tools\Feature\Features\Link\Utils\LinkUtil;
use Espo\Modules\Mcp\Tools\Feature\ToolDefinitionProvider;
use Espo\Modules\Mcp\Tools\JsonSchema\ConstSchema;
use Espo\Modules\Mcp\Tools\JsonSchema\GroupSchema;
use Espo\Modules\Mcp\Tools\JsonSchema\Schema;
use Espo\Modules\Mcp\Tools\JsonSchema\Type\ObjectType;
use Espo\Modules\Mcp\Tools\JsonSchema\Type\StringType;
use Espo\Modules\Mcp\Tools\Mcp\Schema\General\RootObjectSchema;
use Espo\Modules\Mcp\Tools\Mcp\Schema\General\RootSchema;
use Espo\Modules\Mcp\Tools\Mcp\Schema\Tool\Tool;
use Espo\Modules\Mcp\Tools\Mcp\Schema\Tool\ToolAnnotations;
use Espo\ORM\Defs;

/**
 * @implements ToolDefinitionProvider<LinkData>
 */
class LinkToolDefinitionProvider implements ToolDefinitionProvider
{
    private const string DESCRIPTION =
        "Relates a '{entityTypeLabel}' record with another record through a link. Entity type: `{entityType}`.";

    private const string DESC_ID = "Record ID.";

    private const string DESC_LINK = "Link name.";

    private const string DESC_LINK_ITEM = "Foreign entity type: `{foreignEntityType}`.";

    private const string DESC_FOREIGN_ID = "Foreign record ID. " .
        "IDs can be looked up with tools `Find_{EntityType}`.";

    public function __construct(
        private Language $defaultLanguage,
        private Acl $acl,
        private LinkUtil $linkUtil,
        private Defs $defs,
        private Metadata $metadata,
    ) {}

    public function get(Data $data): Tool
    {
        if (!$this->acl->tryCheck($data->entityType)) {
            throw new NoUserAccess("No access to '$data->entityType'.");
        }

        $links = $this->getLinks($data);

        if ($links === []) {
            throw new NoUserAccess("No access to any link of '$data->entityType'.");
        }

        return new Tool(
            name: 'Link_' . $data->entityType,
            inputSchema: new RootObjectSchema($this->prepareInputSchema($data, $links)),
            outputSchema: new RootSchema($this->prepareOutputSchema()),
            description: $this->getDescription($data),
            annotations: new ToolAnnotations(
                readOnlyHint: false,
                destructiveHint: false,
                idempotentHint: true,
                openWorldHint: false,
            ),
        );
    }

    /**
     * @return Link[]
     */
    private function getLinks(LinkData $data): array
    {
        $entityType = $data->entityType;

        $forbiddenLinks = $this->acl->getScopeForbiddenLinkList($entityType, Acl\Table::ACTION_EDIT);

        $links = array_filter($data->links, function (Link $link) use ($entityType, $forbiddenLinks) {
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

    /**
     * @param Link[] $links
     */
    private function prepareInputSchema(LinkData $data, array $links): ObjectType
    {
        $entityType = $data->entityType;

        $properties = [
            'id' => new StringType(
                description: self::DESC_ID,
            ),
            'link' => GroupSchema::createAnyOf(
                schemas: array_map(function (Link $link) use ($data, $entityType) {
                    return new ConstSchema(
                        value: $link->name,
                        title: $this->defaultLanguage->translateLabel($link->name, 'links', $entityType),
                        description: $this->getLinkDescription($data, $link),
                    );
                }, $links),
                description: self::DESC_LINK,
            ),
            'foreignId' => new StringType(
                description: self::DESC_FOREIGN_ID,
            ),
        ];

        return new ObjectType(
            properties: $properties,
            required: [
                'id',
                'link',
                'foreignId',
            ],
            additionalProperties: false,
        );
    }

    private function getLinkDescription(LinkData $data, Link $link): string
    {
        $foreignEntityType = $this->getForeignEntityType($data->entityType, $link->name);

        $description = strtr(self::DESC_LINK_ITEM, [
            '{foreignEntityType}' => $foreignEntityType ?? '',
        ]);

        if ($link->description) {
            $description .= "\n\n" . $link->description;
        }

        return $description;
    }

    private function prepareOutputSchema(): Schema
    {
        return new ObjectType(
            properties: [
                'record' => new ObjectType(
                    properties: [
                        'id' => new StringType(
                            description: "Record ID.",
                        ),
                    ],
                    description: "Record that was related.",
                ),
                'error' => new ObjectType(
                    properties: [
                        'message' => new StringType(
                            description: "Error message.",
                        ),
                        'code' => GroupSchema::createAnyOf(
                            schemas: [
                                new ConstSchema(
                                    value: 400,
                                    description: "Bad request.",
                                ),
                                new ConstSchema(
                                    value: 403,
                                    description: "No 'edit' access to the record or no access to the foreign record. " .
                                        "Or other access error.",
                                ),
                                new ConstSchema(
                                    value: 404,
                                    description: "Record or foreign record not found.",
                                ),
                            ],
                            description: 'Error code.',
                        ),
                    ],
                ),
            ],
        );
    }

    private function getDescription(LinkData $data): string
    {
        return strtr(self::DESCRIPTION, [
            '{entityTypeLabel}' => $this->defaultLanguage->translateLabel($data->entityType, 'scopeNames'),
            '{entityType}' => $data->entityType,
        ]);
    }

    /**
     */
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
