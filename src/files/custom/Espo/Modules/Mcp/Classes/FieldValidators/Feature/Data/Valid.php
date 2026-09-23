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

namespace Espo\Modules\Mcp\Classes\FieldValidators\Feature\Data;

use Espo\Core\FieldValidation\Validator;
use Espo\Core\FieldValidation\Validator\Data;
use Espo\Core\FieldValidation\Validator\Failure;
use Espo\Core\Utils\Log;
use Espo\Modules\Mcp\Entities\Feature;
use Espo\Modules\Mcp\Tools\Feature\DataFactory;
use Espo\Modules\Mcp\Tools\Feature\DataValidatorFactory;
use Espo\Modules\Mcp\Tools\Feature\Exceptions\BadFeatureData;
use Espo\Modules\Mcp\Tools\Feature\Exceptions\UnsupportedType;
use Espo\Modules\Mcp\Tools\Feature\Validator\Failure as ValidatorFailure;
use Espo\ORM\Entity;

/**
 * @implements Validator<Feature>
 */
class Valid implements Validator
{
    public function __construct(
        private DataFactory $factory,
        private DataValidatorFactory $dataValidatorFactory,
        private Log $log,
    ) {}

    public function validate(Entity $entity, string $field, Data $data): ?Failure
    {
        if (!$entity->has(Feature::FIELD_TYPE)) {
            return null;
        }

        try {
            $recordData = $this->factory->createForFeature($entity);
        } catch (BadFeatureData|UnsupportedType $e) {
            $this->log->info("Invalid data.", ['exception' => $e]);

            return Failure::create();
        }

        try {
            $dataValidator = $this->dataValidatorFactory->create($entity->getType());
        } catch (UnsupportedType) {
            return null;
        }

        $failures = $dataValidator->validate($recordData);

        if ($failures === []) {
            return null;
        }

        $messages = array_map(fn (ValidatorFailure $it) => $it->message ?? $it->field, $failures);

        $this->log->info("Invalid data. {messages}", [
            'messages' => implode(' ', $messages),
        ]);

        return Failure::create();
    }
}
