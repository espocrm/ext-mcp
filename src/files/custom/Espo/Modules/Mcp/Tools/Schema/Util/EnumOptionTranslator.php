<?php
/**LICENSE**/

namespace Espo\Modules\Mcp\Tools\Schema\Util;

use Espo\Core\Utils\Language;
use Espo\ORM\Defs;

class EnumOptionTranslator
{
    public function __construct(
        private Language $defaultLanguage,
        private Defs $ormDefs,
    ) {}

    public function translate(string $value, string $field, string $entityType): string
    {
        $fieldDefs = $this->ormDefs
            ->tryGetEntity($entityType)
            ?->tryGetField($field);

        if ($fieldDefs) {
            /** @var ?string $ref */
            $ref = $fieldDefs->getParam('optionsReference');

            if ($ref && str_contains($ref, '.')) {
                [$entityType, $field] = explode('.', $ref);
            }
        }

        return $this->defaultLanguage->translateOption($value, $field, $entityType);
    }
}
