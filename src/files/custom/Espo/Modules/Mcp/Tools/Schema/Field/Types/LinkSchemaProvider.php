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
class LinkSchemaProvider implements SchemaProvider
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
        $nameAttribute = $field . 'Name';

        $fieldDefs = $this->ormDefs->getEntity($entityType)->getField($field);
        $linkDefs = $this->ormDefs->getEntity($entityType)->tryGetRelation($field);
        $idAttributeDefs = $this->ormDefs->getEntity($entityType)->getAttribute($field . 'Id');

        $foreignEntityType = $linkDefs?->tryGetForeignEntityType() ?? $fieldDefs->getParam('entity');

        if (!$foreignEntityType) {
            return new Result();
        }

        if ($params->isWriteAction() && !$this->acl->checkScope($foreignEntityType)) {
            return new Result();
        }

        $required = [];

        if (
            $fieldDefs->getParam(FieldParam::REQUIRED) &&
            $idAttributeDefs->getParam(AttributeParam::DEFAULT) === null
        ) {
            $required[] = $idAttribute;
        }

        $label = $this->defaultLanguage->translateLabel($field, 'fields', $entityType);
        $foreignScopeLabel = $this->defaultLanguage->translateLabel($foreignEntityType, 'scopeNames');

        $idProperty = new StringType(
            title: "$label (ID)",
            description:
                "An ID attribute of the '$label' link field. Field name: `$params->field`. " .
                "Specifies the '$foreignScopeLabel' record ID. Foreign type: `$foreignEntityType`. " .
                "Tool to retrieve IDs: `Find.$foreignEntityType`."
        );

        if (!$fieldDefs->getParam(FieldParam::REQUIRED)) {
            $idProperty = Util::wrapWithNull($idProperty);
        }

        $properties = [
            $idAttribute => $idProperty,
        ];

        if (!$params->isWriteAction()) {
            $nameProperty = new StringType(
                title: "$label (Name)",
                description:
                    "A Name attribute of '$label' link field. Field name: `$params->field`. " .
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
