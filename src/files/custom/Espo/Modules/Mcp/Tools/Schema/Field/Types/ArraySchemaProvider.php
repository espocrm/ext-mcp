<?php
/**LICENSE**/

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
