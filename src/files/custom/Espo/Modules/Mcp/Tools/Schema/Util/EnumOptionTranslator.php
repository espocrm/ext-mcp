<?php
/**LICENSE**/

namespace Espo\Modules\Mcp\Tools\Schema\Util;

use Espo\Core\Utils\Language;

/**
 * @todo Translate referenced.
 */
class EnumOptionTranslator
{
    public function __construct(
        private Language $defaultLanguage,
    ) {}

    public function translate(string $value, string $field, string $entityType): string
    {
        return $this->defaultLanguage->translateOption($value, $field, $entityType);
    }
}
