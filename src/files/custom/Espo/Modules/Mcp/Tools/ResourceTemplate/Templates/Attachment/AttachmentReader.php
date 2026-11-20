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

namespace Espo\Modules\Mcp\Tools\ResourceTemplate\Templates\Attachment;

use Espo\Core\Acl;
use Espo\Core\FileStorage\Manager;
use Espo\Core\Utils\Metadata;
use Espo\Entities\Attachment;
use Espo\Entities\Note;
use Espo\Modules\Mcp\Entities\Endpoint;
use Espo\Modules\Mcp\Tools\Feature\DataFactory;
use Espo\Modules\Mcp\Tools\Feature\Exceptions\BadFeatureData;
use Espo\Modules\Mcp\Tools\Feature\Exceptions\UnsupportedType;
use Espo\Modules\Mcp\Tools\Feature\Features\Find\FindData;
use Espo\Modules\Mcp\Tools\Feature\Features\Read\ReadData;
use Espo\Modules\Mcp\Tools\Feature\Features\RecordStream\RecordStreamData;
use Espo\Modules\Mcp\Tools\Mcp\ConfigDataProvider;
use Espo\Modules\Mcp\Tools\Mcp\Exceptions\Espo\NoAccessError;
use Espo\Modules\Mcp\Tools\Mcp\Exceptions\Espo\TooLargeError;
use Espo\Modules\Mcp\Tools\Mcp\Exceptions\InvalidParamsError;
use Espo\Modules\Mcp\Tools\Mcp\Schema\Resource\BlobResourceContents;
use Espo\Modules\Mcp\Tools\Mcp\Schema\Resource\ReadResourceResult;
use Espo\Modules\Mcp\Tools\Mcp\Schema\Resource\TextResourceContents;
use Espo\Modules\Mcp\Tools\ResourceTemplate\Reader;
use Espo\Modules\Mcp\Tools\ResourceTemplate\Params;
use Espo\ORM\EntityManager;

/**
 * @implements Reader<AttachmentParams>
 */
class AttachmentReader implements Reader
{
    public function __construct(
        private EntityManager $entityManager,
        private Acl $acl,
        private Manager $fileStorageManager,
        private ConfigDataProvider $configDataProvider,
        private Endpoint $endpoint,
        private DataFactory $dataFactory,
        private Metadata $metadata,
    ) {}

    /**
     * @throws InvalidParamsError
     * @throws TooLargeError
     * @throws NoAccessError
     */
    public function read(Params $params): ReadResourceResult
    {
        $id = $params->id;

        $attachment = $this->entityManager->getRDBRepositoryByClass(Attachment::class)->getById($id);

        if (!$attachment) {
            throw InvalidParamsError::create("Attachment resource not found.", [
                'id' => $id,
                'uri' => $params->getUri(),
            ]);
        }

        if (!$this->acl->checkEntityRead($attachment)) {
            throw new NoAccessError("No access to attachment '$id'.");
        }

        if (!$this->checkFeatureAccess($attachment)) {
            throw new NoAccessError("No MCP access to attachment '$id'.");
        }

        $size = $attachment->getSize() ?? $this->fileStorageManager->getSize($attachment);

        if ($size > $this->configDataProvider->getAttachmentResourceMaxSize() * 1024 * 1024) {
            throw TooLargeError::create("Attachment exceeds max size.", [
                'id' => $id,
                'uri' => $params->getUri(),
            ]);
        }

        $contents = $this->fileStorageManager->getContents($attachment);

        if (
            $attachment->getType() && str_starts_with($attachment->getType(), 'text/') &&
            mb_check_encoding($contents, 'UTF-8')
        ) {
            $resourceContents = new TextResourceContents(
                uri: $params->getUri(),
                text: $contents,
                mimeType: $attachment->getType(),
            );
        } else {
            $resourceContents = new BlobResourceContents(
                uri: $params->getUri(),
                blob: base64_encode($contents),
                mimeType: $attachment->getType(),
            );
        }

        return new ReadResourceResult(
            contents: [$resourceContents],
        );
    }

    private function checkFeatureAccess(Attachment $attachment): bool
    {
        $parent = $attachment->getParent();
        $related = $attachment->getRelated();
        $field = $attachment->getTargetField();

        if (
            !$parent && !$related ||
            !$field
        ) {
            return false;
        }

        if ($parent?->getEntityType() === Note::ENTITY_TYPE) {
            $note = $this->entityManager->getRDBRepositoryByClass(Note::class)->getById($parent->getId());

            if (!$note) {
                return false;
            }

            $feature = $this->endpoint->getFeatures()
                ->find(fn ($it) => $it->isActive() && $it->getType() === RecordStreamData::TYPE);

            if (!$feature) {
                return false;
            }

            try {
                $data = $this->dataFactory->createForFeature($feature);
            } catch (BadFeatureData|UnsupportedType) {
                return false;
            }

            if (!$data instanceof RecordStreamData) {
                return false;
            }

            return
                in_array($note->getParentType(), $data->entityTypes, true) ||
                in_array($note->getSuperParentType(), $data->entityTypes, true);
        }

        $entityType = $parent?->getEntityType() ?? $related?->getEntityType();

        if (!$entityType) {
            return false;
        }

        $features = $this->endpoint->getFeatures()
            ->filter(fn ($it) => $it->isActive() && in_array($it->getType(), [ReadData::TYPE, FindData::TYPE], true));

        foreach ($features as $feature) {
            try {
                $data = $this->dataFactory->createForFeature($feature);
            } catch (BadFeatureData|UnsupportedType) {
                continue;
            }

            if (!$data instanceof ReadData && !$data instanceof FindData) {
                continue;
            }

            if ($data->entityType !== $entityType) {
                continue;
            }

            // Mandatory fields will be implemented in the future.
            if ($this->metadata->get("app.mcpScopes.$entityType.fields.read")) {
                return true;
            }

            $fields = array_map(fn ($it) => $it->name, $data->selectFields);

            if (in_array($field, $fields, true)) {
                return true;
            }
        }

        return false;
    }
}
