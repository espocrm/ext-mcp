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

namespace Espo\Modules\Mcp\Classes\FieldLoaders\Feature;

use Espo\Core\FieldProcessing\Loader;
use Espo\Core\FieldProcessing\Loader\Params;
use Espo\Modules\Mcp\Entities\Feature;
use Espo\Modules\Mcp\Tools\Feature\DataFactory;
use Espo\Modules\Mcp\Tools\Feature\Exceptions\BadFeatureData;
use Espo\Modules\Mcp\Tools\Feature\Exceptions\UnsupportedType;
use Espo\Modules\Mcp\Tools\Feature\TextComposerFactory;
use Espo\ORM\Entity;

/**
 * @implements Loader<Feature>
 */
class TextLoader implements Loader
{
    public function __construct(
        private DataFactory $dataFactory,
        private TextComposerFactory $textComposerFactory,
    ) {}

    public function process(Entity $entity, Params $params): void
    {
        try {
            $data = $this->dataFactory->createForFeature($entity);
        } catch (BadFeatureData|UnsupportedType) {
            return;
        }

        try {
            $textComposer = $this->textComposerFactory->create($entity->getType());
        } catch (UnsupportedType) {
            return;
        }

        $text = $textComposer->compose($data);

        $entity->set(Feature::FIELD_TEXT, $text);
    }
}
