<?php
/**LICENSE**/

namespace Espo\Modules\Mcp\Tools\Schema\Field\Types;

use Espo\Core\Utils\Language;
use Espo\Modules\Mcp\Tools\JsonSchema\EnumSchema;
use Espo\Modules\Mcp\Tools\JsonSchema\GroupSchema;
use Espo\Modules\Mcp\Tools\JsonSchema\Type\StringType;
use Espo\Modules\Mcp\Tools\Schema\Field\FieldSchemaProvider\Params;
use Espo\Modules\Mcp\Tools\Schema\Field\FieldSchemaProvider\Result;
use Espo\Modules\Mcp\Tools\Schema\Field\SchemaProvider;
use Espo\Modules\Mcp\Tools\Schema\Field\Util;
use Espo\Modules\Mcp\Tools\Schema\Util\EnumOptionsProvider;
use Espo\ORM\Defs;
use Espo\ORM\Defs\Params\FieldParam;

/**
 * @noinspection PhpUnused
 */
class VarcharSchemaProvider implements SchemaProvider
{
    private const int MAX_LENGTH = 255;

    public function __construct(
        private Defs $ormDefs,
        private Language $defaultLanguage,
        private EnumOptionsProvider $enumOptionsProvider,
    ) {}

    public function get(Params $params): Result
    {
        $fieldDefs = $this->ormDefs->getEntity($params->entityType)->getField($params->field);

        $maxLength = null;

        if ($params->isWriteAction()) {
            $maxLength = $fieldDefs->getParam(FieldParam::MAX_LENGTH) ?? self::MAX_LENGTH;
        }

        $required = [];

        if ($fieldDefs->getParam(FieldParam::REQUIRED) && $fieldDefs->getParam(FieldParam::DEFAULT) === null) {
            $required[] = $params->field;
        }

        $label = $this->defaultLanguage->translateLabel($params->field, 'fields', $params->entityType);

        $description = "Single-line.";

        $property = new StringType(
            maxLength: $maxLength,
            title: $label,
            description: $description,
        );

        $options = $this->enumOptionsProvider->get($fieldDefs);

        if ($options) {
            $property = GroupSchema::createAnyOf(
                schemas: [
                    $property->withDescription(null),
                    new EnumSchema(
                        values: $options,
                    ),
                ],
                title: $label,
                description: $description,
            );
        }

        if (!$fieldDefs->getParam(FieldParam::REQUIRED)) {
            $property = Util::wrapWithNull($property);
        }

        return new Result(
            properties: [
                $params->field => $property,
            ],
            required: $required,
        );
    }
}
