<?php
/**LICENSE**/

namespace Espo\Modules\Mcp\Schema\FieldFilter\Types;

use Espo\Core\Acl;
use Espo\Core\Select\Where\Item\Type;
use Espo\Core\Utils\Language;
use Espo\Modules\Mcp\Schema\FieldFilter\FieldFilterSchemaProvider;
use Espo\Modules\Mcp\Tools\JsonSchema\ConstSchema;
use Espo\Modules\Mcp\Tools\JsonSchema\GroupKeyword;
use Espo\Modules\Mcp\Tools\JsonSchema\GroupSchema;
use Espo\Modules\Mcp\Tools\JsonSchema\Type\ObjectType;
use Espo\Modules\Mcp\Tools\JsonSchema\Type\StringType;
use Espo\ORM\Defs;
use Espo\ORM\Defs\Params\FieldParam;

/**
 * @noinspection PhpUnused
 */
class LinkFilterSchemaProvider implements FieldFilterSchemaProvider
{
    public function __construct(
        private Language $defaultLanguage,
        private Defs $ormDefs,
        private Acl $acl,
    ) {}
    /**
     * @inheritDoc
     */
    public function get(string $entityType, string $field): array
    {
        $fieldDefs = $this->ormDefs->getEntity($entityType)->getField($field);
        $linkDefs = $this->ormDefs->getEntity($entityType)->tryGetRelation($field);

        if (!$linkDefs) {
            return [];
        }

        $foreignEntityType = $linkDefs->tryGetForeignEntityType();

        if (!$foreignEntityType) {
            return [];
        }

        if (!$this->acl->checkScope($foreignEntityType)) {
            return [];
        }

        $label = $this->defaultLanguage->translateLabel($field, 'fields', $entityType);

        $foreignScopeLabel = $this->defaultLanguage->translateLabel($foreignEntityType, 'scopeNames');

        return [
            new ObjectType(
                properties: [
                    'attribute' => new ConstSchema(
                        value: $field . 'Id',
                        description: "Attribute name. Field name plus an `Id` prefix.",
                    ),
                    'type' => new GroupSchema(
                        keyword: GroupKeyword::anyOff,
                        schemas: [
                            new ConstSchema(value: Type::EQUALS),
                            new ConstSchema(value: Type::NOT_EQUALS),
                        ],
                    ),
                    'value' => new StringType(
                        description: "'$foreignScopeLabel' record ID. Tool to retrieve IDs: `Find.$foreignEntityType`."
                    ),
                ],
                required: [
                    'attribute',
                    'type',
                    'value',
                ],
                description: "'$label' field filter.",
            ),
            ...(
                !$fieldDefs->getParam(FieldParam::REQUIRED) ?
                    [
                        new ObjectType(
                            properties: [
                                'attribute' => new ConstSchema(value: $field . 'Id'),
                                'type' => new GroupSchema(
                                    keyword: GroupKeyword::anyOff,
                                    schemas: [
                                        new ConstSchema(value: Type::IS_NOT_NULL),
                                        new ConstSchema(value: Type::IS_NULL),
                                    ],
                                ),
                            ],
                            required: [
                                'attribute',
                                'type',
                            ],
                            description: "'$label' field filter checking if the value is empty or not.",
                        )
                    ]: []
            ),
        ];
    }
}
