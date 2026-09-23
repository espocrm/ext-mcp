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

use Espo\Core\Acl;
use Espo\Core\Utils\Language;
use Espo\Modules\Mcp\Tools\JsonSchema\ConstSchema;
use Espo\Modules\Mcp\Tools\JsonSchema\GroupKeyword;
use Espo\Modules\Mcp\Tools\JsonSchema\GroupSchema;
use Espo\Modules\Mcp\Tools\JsonSchema\Type\StringType;
use Espo\Modules\Mcp\Tools\Schema\Field\FieldSchemaProvider\Params;
use Espo\Modules\Mcp\Tools\Schema\Field\FieldSchemaProvider\Result;
use Espo\Modules\Mcp\Tools\Schema\Field\SchemaProvider;
use Espo\Modules\Mcp\Tools\Schema\Field\Util;
use Espo\ORM\Defs;
use Espo\ORM\Defs\Params\AttributeParam;
use Espo\ORM\Defs\Params\FieldParam;

/**
 * @noinspection PhpUnused
 */
class LinkParentSchemaProvider implements SchemaProvider
{
    public function __construct(
        private Defs $ormDefs,
        private Language $defaultLanguage,
        private Acl $acl,
    ) {}

    public function get(Params $params): Result
    {
        $entityType = $params->entityType;
        $field = $params->field;

        $idAttribute = $field . 'Id';
        $typeAttribute = $field . 'Type';
        $nameAttribute = $field . 'Name';

        $fieldDefs = $this->ormDefs->getEntity($entityType)->getField($field);
        $idAttributeDefs = $this->ormDefs->getEntity($entityType)->getAttribute($field . 'Id');

        $foreignEntityTypes = $fieldDefs->getParam('entityList') ?? [];

        if (!is_array($foreignEntityTypes) || !$foreignEntityTypes) {
            return new Result();
        }

        $foreignEntityTypes = array_filter($foreignEntityTypes, fn ($it) => $this->acl->checkScope($it));
        $foreignEntityTypes = array_values($foreignEntityTypes);

        if (!$foreignEntityTypes) {
            return new Result();
        }

        $required = [];

        if (
            $fieldDefs->getParam(FieldParam::REQUIRED) &&
            $idAttributeDefs->getParam(AttributeParam::DEFAULT) === null
        ) {
            $required[] = $idAttribute;
            $required[] = $typeAttribute;
        }

        $label = $this->defaultLanguage->translateLabel($field, 'fields', $entityType);

        $idProperty = new StringType(
            title: "$label (ID)",
            description:
                "An ID attribute of the '$label' link-parent (polymorphic) field. Field name: `$params->field`. " .
                "Specifies the foreign record ID." .
                "Tool to retrieve IDs: `Find.{entityType}`."
        );

        $typeSchemas = array_map(function ($it) {
            return new ConstSchema(
                value: $it,
                title: $this->defaultLanguage->translateLabel($it, 'scopeNames'),
            );
        }, $foreignEntityTypes);

        if (!$fieldDefs->getParam(FieldParam::REQUIRED)) {
            $typeSchemas[] = new ConstSchema(value: null);
        }

        $typeProperty = new GroupSchema(
            keyword: GroupKeyword::anyOf,
            schemas: $typeSchemas,
            title: "$label (Type)",
            description:
                "A Type attribute of the '$label' link-parent (polymorphic) field. Field name: `$params->field`. " .
                "Specifies the foreign entity type.",
        );

        if (!$fieldDefs->getParam(FieldParam::REQUIRED)) {
            $idProperty = Util::wrapWithNull($idProperty);
        }

        $properties = [
            $idAttribute => $idProperty,
            $typeAttribute => $typeProperty,
        ];

        if (!$params->isWriteAction()) {
            $nameProperty = new StringType(
                title: "$label (Name)",
                description:
                    "A Name attribute of '$label' link-parent field. Field name: `$params->field`. " .
                    "Contains the related record name.",
            );

            $properties[$nameAttribute] = Util::wrapWithNull($nameProperty);
        }

        return new Result(
            properties: $properties,
            required: $required,
        );
    }
}
