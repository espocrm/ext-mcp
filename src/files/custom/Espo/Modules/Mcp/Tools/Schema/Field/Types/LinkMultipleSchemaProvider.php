<?php
/**LICENSE**/

namespace Espo\Modules\Mcp\Tools\Schema\Field\Types;

use Espo\Core\Acl;
use Espo\Core\Utils\Language;
use Espo\Modules\Mcp\Tools\JsonSchema\Type\ArrayType;
use Espo\Modules\Mcp\Tools\JsonSchema\Type\ObjectType;
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
class LinkMultipleSchemaProvider implements SchemaProvider
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

        $idsAttribute = $field . 'Ids';
        $namesAttribute = $field . 'Names';

        $fieldDefs = $this->ormDefs->getEntity($entityType)->getField($field);
        $linkDefs = $this->ormDefs->getEntity($entityType)->tryGetRelation($field);
        $idsAttributeDefs = $this->ormDefs->getEntity($entityType)->getAttribute($field . 'Ids');

        $foreignEntityType = $linkDefs?->tryGetForeignEntityType() ?? $fieldDefs->getParam('entity');

        if (!$foreignEntityType) {
            return new Result();
        }

        if ($params->isWriteAction() && !$this->acl->checkScope($foreignEntityType)) {
            return new Result();
        }

        $required = [];
        $minItems = null;

        if (
            $fieldDefs->getParam(FieldParam::REQUIRED) &&
            $idsAttributeDefs->getParam(AttributeParam::DEFAULT) === null
        ) {
            $required[] = $idsAttribute;
            $minItems = 1;
        }

        $label = $this->defaultLanguage->translateLabel($field, 'fields', $entityType);
        $foreignScopeLabel = $this->defaultLanguage->translateLabel($foreignEntityType, 'scopeNames');

        $idsProperty = new ArrayType(
            items: new StringType(
                description: "'$foreignEntityType' record ID.",
            ),
            minItems: $minItems,
            title: "$label (IDs)",
            description:
                "An IDs attribute of the '$label' link-multiple field. Field name: `$params->field`. " .
                "Specifies the '$foreignScopeLabel' record IDs. Foreign type: `$foreignEntityType`. " .
                "Tool to retrieve IDs: `Find.$foreignEntityType`."
        );

        $properties = [
            $idsAttribute => $idsProperty,
        ];

        if (!$params->isWriteAction()) {
            $namesProperty = new ObjectType(
                additionalProperties: Util::wrapWithNull(
                    new StringType(
                        description: "Record name.",
                    ),
                ),
                description:
                    "Names attribute of '$label' link-multiple field. Field name: `$params->field`. " .
                    "Contains the mapping of IDs to record names.",
            );

            $properties[$namesAttribute] = $namesProperty;
        }

        return new Result(
            properties: $properties,
            required: $required,
        );
    }
}
