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

namespace Espo\Modules\Mcp\Tools\Feature\Utils;

use Espo\Core\ORM\Entity as CoreEntity;
use Espo\Core\ORM\Type\FieldType;
use Espo\Modules\Mcp\Tools\Mcp\Schema\Resource\ResourceLink;
use Espo\Modules\Mcp\Tools\ResourceTemplate\Templates\Attachment\Utils\Uri;
use Espo\ORM\Defs;
use Espo\ORM\Entity;
use stdClass;

class AttachmentResourceLinksPreparator
{
    public function __construct(
        private Defs $defs,
    ) {}

    /**
     * @param string[] $fields
     * @return ResourceLink[]
     */
    public function get(Entity $entity, array $fields): array
    {
        if (!$entity instanceof CoreEntity) {
            return [];
        }

        $entityDefs = $this->defs->getEntity($entity->getEntityType());

        $output = [];

        foreach ($fields as $field) {
            $fieldDefs = $entityDefs->tryGetField($field);

            if ($fieldDefs?->getType() === FieldType::ATTACHMENT_MULTIPLE) {
                array_push($output, ...$this->getAttachmentMultiple($entity, $field));

                continue;
            }

            if (in_array($fieldDefs?->getType(), [FieldType::FILE, FieldType::IMAGE])) {
                $resourceLink = $this->getFile($entity, $field);

                if ($resourceLink) {
                    $output[] = $resourceLink;
                }
            }
        }

        return $output;
    }

    /**
     * @return ResourceLink[]
     */
    private function getAttachmentMultiple(CoreEntity $entity, string $field): array
    {
        if (!$entity->has($field . 'Ids')) {
            return [];
        }

        $ids = $entity->getLinkMultipleIdList($field);

        /** @var stdClass $types */
        $types = $entity->get($field . 'Types') ?? (object) [];

        $output = [];

        foreach ($ids as $id) {
            $output[] = new ResourceLink(
                name: $entity->getLinkMultipleName($field, $id) ?? $id,
                uri: Uri::compose($id),
                description: "Attachment of the `$field` field.",
                mimeType: $types->$id ?? null,
            );
        }

        return $output;
    }

    private function getFile(CoreEntity $entity, string $field): ?ResourceLink
    {
        if (!$entity->has($field . 'Id')) {
            return null;
        }

        $id = $entity->get($field . 'Id');
        $name = $entity->get($field . 'Name');

        if (!$id) {
            return null;
        }

        return new ResourceLink(
            name: $name ?? $id,
            uri: Uri::compose($id),
            description: "Attachment of the `$field` field.",
        );
    }
}
