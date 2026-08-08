<?php
/**LICENSE**/

namespace Espo\Modules\Mcp\Tools\Schema\Field\Types;

use Espo\Core\Acl;
use Espo\Core\Utils\Language;
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
            description:
                "ID attribute of the '$label' link-parent (polymorphic) field. Field name: `$params->field`. " .
                "Specifies the foreign record ID." .
                "Tool to retrieve IDs: `Find.{entityType}`."
        );

        $typeProperty = new StringType(
            description:
                "Type attribute of the '$label' link-parent (polymorphic) field. Field name: `$params->field`. " .
                "Specifies the foreign entity type.",
        );

        if (!$fieldDefs->getParam(FieldParam::REQUIRED)) {
            $idProperty = Util::wrapWithNull($idProperty);
        }

        if (!$fieldDefs->getParam(FieldParam::REQUIRED)) {
            $typeProperty = Util::wrapWithNull($typeProperty);
        }

        $properties = [
            $idAttribute => $idProperty,
            $typeAttribute => $typeProperty,
        ];

        if (!$params->isWriteAction()) {
            $nameProperty = new StringType(
                description:
                    "Name attribute of '$label' link-parent field. Field name: `$params->field`. " .
                    "Contains the related record name.",
            );

            if (!$fieldDefs->getParam(FieldParam::REQUIRED)) {
                $nameProperty = Util::wrapWithNull($nameProperty);
            }

            $properties[$nameAttribute] = $nameProperty;
        }

        return new Result(
            properties: $properties,
            required: $required,
        );
    }
}
