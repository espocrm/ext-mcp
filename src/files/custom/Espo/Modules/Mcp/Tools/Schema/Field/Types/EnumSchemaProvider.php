<?php
/**LICENSE**/

namespace Espo\Modules\Mcp\Tools\Schema\Field\Types;

use Espo\Core\Utils\Language;
use Espo\Modules\Mcp\Tools\JsonSchema\ConstSchema;
use Espo\Modules\Mcp\Tools\JsonSchema\GroupSchema;
use Espo\Modules\Mcp\Tools\Schema\Field\FieldSchemaProvider\Params;
use Espo\Modules\Mcp\Tools\Schema\Field\FieldSchemaProvider\Result;
use Espo\Modules\Mcp\Tools\Schema\Field\SchemaProvider;
use Espo\Modules\Mcp\Tools\Schema\Util\EnumOptionsProvider;
use Espo\Modules\Mcp\Tools\Schema\Util\EnumOptionTranslator;
use Espo\ORM\Defs;
use Espo\ORM\Defs\Params\FieldParam;

/**
 * @noinspection PhpUnused
 */
class EnumSchemaProvider implements SchemaProvider
{
    public function __construct(
        private Defs $ormDefs,
        private Language $defaultLanguage,
        private EnumOptionsProvider $enumOptionsProvider,
        private EnumOptionTranslator $enumOptionTranslator,
    ) {}

    public function get(Params $params): Result
    {
        $fieldDefs = $this->ormDefs->getEntity($params->entityType)->getField($params->field);

        $required = [];

        if ($fieldDefs->getParam(FieldParam::REQUIRED) && $fieldDefs->getParam(FieldParam::DEFAULT) === null) {
            $required[] = $params->field;
        }

        $label = $this->defaultLanguage->translateLabel($params->field, 'fields', $params->entityType);

        $options = $this->enumOptionsProvider->get($fieldDefs);
        $nonEmptyOptions = $this->getNonEmptyOptions($fieldDefs);

        if (!$options || !$nonEmptyOptions) {
            return new Result();
        }

        $schemas = [
            ...array_map(function (string $it) use ($params) {
                return new ConstSchema(
                    value: $it,
                    title: $this->enumOptionTranslator->translate($it, $params->field, $params->entityType),
                );
            }, $nonEmptyOptions)
        ];

        if (in_array('', $options, true) && !$fieldDefs->getParam(FieldParam::REQUIRED)) {
            $schemas[] = new ConstSchema(value: null);
        }

        $property = GroupSchema::createAnyOf(
            schemas: $schemas,
            title: $label,
        );

        return new Result(
            properties: [
                $params->field => $property,
            ],
            required: $required,
        );
    }

    /**
     * @return ?string[]
     */
    private function getNonEmptyOptions(Defs\FieldDefs $fieldDefs): ?array
    {
        $options = $this->enumOptionsProvider->get($fieldDefs);

        if ($options === null) {
            return null;
        }

        $options = array_filter($options, fn ($it) => $it !== '');

        return array_values($options);
    }
}
