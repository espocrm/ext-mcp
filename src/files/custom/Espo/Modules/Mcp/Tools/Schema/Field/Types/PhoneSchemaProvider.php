<?php
/**LICENSE**/

namespace Espo\Modules\Mcp\Tools\Schema\Field\Types;

use Espo\Core\Utils\Config;
use Espo\Core\Utils\Language;
use Espo\Modules\Mcp\Tools\JsonSchema\Type\StringType;
use Espo\Modules\Mcp\Tools\Schema\Field\FieldSchemaProvider\Params;
use Espo\Modules\Mcp\Tools\Schema\Field\FieldSchemaProvider\Result;
use Espo\Modules\Mcp\Tools\Schema\Field\SchemaProvider;
use Espo\Modules\Mcp\Tools\Schema\Field\Util;
use Espo\ORM\Defs;
use Espo\ORM\Defs\Params\FieldParam;

/**
 * @noinspection PhpUnused
 */
class PhoneSchemaProvider implements SchemaProvider
{
    private const int MAX_LENGTH = 36;

    public function __construct(
        private Defs $ormDefs,
        private Language $defaultLanguage,
        private Config $config,
    ) {}

    public function get(Params $params): Result
    {
        $fieldDefs = $this->ormDefs->getEntity($params->entityType)->getField($params->field);

        $maxLength = null;

        if ($params->isWriteAction()) {
            $maxLength = self::MAX_LENGTH;
        }

        $required = [];

        if ($fieldDefs->getParam(FieldParam::REQUIRED) && $fieldDefs->getParam(FieldParam::DEFAULT) === null) {
            $required[] = $params->field;
        }

        $label = $this->defaultLanguage->translateLabel($params->field, 'fields', $params->entityType);

        $description = "A phone number.";

        if ($this->config->get('phoneNumberInternational')) {
            $description .= " Only international format. E.g. `+111111111111`.";

            if ($this->config->get('phoneNumberExtensions')) {
                $description .= " Extensions are supported.";
            }
        }

        $property = new StringType(
            maxLength: $maxLength,
            title: $label,
            description: $description,
        );

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
