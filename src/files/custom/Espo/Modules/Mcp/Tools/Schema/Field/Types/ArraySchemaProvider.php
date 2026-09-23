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

namespace Espo\Modules\Mcp\Tools\Schema\Field\Types;

use Espo\Core\Utils\Language;
use Espo\Modules\Mcp\Tools\JsonSchema\ConstSchema;
use Espo\Modules\Mcp\Tools\JsonSchema\GroupSchema;
use Espo\Modules\Mcp\Tools\JsonSchema\StringFormat;
use Espo\Modules\Mcp\Tools\JsonSchema\Type\ArrayType;
use Espo\Modules\Mcp\Tools\JsonSchema\Type\StringType;
use Espo\Modules\Mcp\Tools\Schema\Field\FieldSchemaProvider\Params;
use Espo\Modules\Mcp\Tools\Schema\Field\FieldSchemaProvider\Result;
use Espo\Modules\Mcp\Tools\Schema\Field\SchemaProvider;
use Espo\Modules\Mcp\Tools\Schema\Util\EnumOptionsProvider;
use Espo\Modules\Mcp\Tools\Schema\Util\EnumOptionTranslator;
use Espo\ORM\Defs;
use Espo\ORM\Defs\FieldDefs;
use Espo\ORM\Defs\Params\FieldParam;


/**
 * @noinspection PhpUnused
 */
class ArraySchemaProvider implements SchemaProvider
{
    private const int MAX_LENGTH = 100;

    protected bool $noOptions = false;

    public function __construct(
        private Defs $ormDefs,
        private Language $defaultLanguage,
        private EnumOptionsProvider $enumOptionsProvider,
        private EnumOptionTranslator $enumOptionTranslator,
    ) {}

    public function get(Params $params): Result
    {
        $entityType = $params->entityType;
        $field = $params->field;

        $fieldDefs = $this->ormDefs->getEntity($params->entityType)->getField($params->field);

        $required = [];
        $minItems = null;
        $maxCount = null;

        if ($fieldDefs->getParam(FieldParam::REQUIRED) && $params->isWriteAction()) {
            $required[] = $params->field;

            $minItems = 1;
        }

        if ($params->isWriteAction()) {
            $maxCount = $fieldDefs->getParam('maxCount');
        }

        $label = $this->defaultLanguage->translateLabel($params->field, 'fields', $params->entityType);

        $options = $this->enumOptionsProvider->get($fieldDefs);

        $items = new StringType(
            maxLength: $fieldDefs->getParam('maxItemLength') ?? self::MAX_LENGTH,
            format: $this->getFormat($fieldDefs),
        );

        if ($options && !$this->noOptions) {
            $items = GroupSchema::createAnyOf(
                schemas: [
                    ...array_map(function (string $it) use ($entityType, $field) {
                        return new ConstSchema(
                            value: $it,
                            title: $this->enumOptionTranslator->translate($it, $field, $entityType),
                        );
                    }, $options)
                ],
            );
        }

        return new Result(
            properties: [
                $params->field => new ArrayType(
                    items: $items,
                    minItems: $minItems,
                    maxItems: $maxCount,
                    uniqueItems: true,
                    title: $label,
                    description: $this->getDescription($fieldDefs),
                ),
            ],
            required: $required,
        );
    }

    /**
     * @noinspection PhpUnusedParameterInspection
     */
    protected function getFormat(FieldDefs $fieldDefs): ?StringFormat
    {
        return null;
    }

    /**
     * @noinspection PhpUnusedParameterInspection
     */
    protected function getDescription(FieldDefs $fieldDefs): ?string
    {
        return null;
    }
}
