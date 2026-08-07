<?php
/**LICENSE**/

namespace Espo\Modules\Mcp\Tools\Schema\Field\Types;

use Espo\Modules\Mcp\Tools\JsonSchema\StringFormat;
use Espo\ORM\Defs\FieldDefs;

/**
 * @noinspection PhpUnused
 */
class UrlMultipleSchemaProvider extends ArraySchemaProvider
{
    protected bool $noOptions = true;

    protected function getFormat(FieldDefs $fieldDefs): ?StringFormat
    {
        return StringFormat::uri;
    }

    protected function getDescription(FieldDefs $fieldDefs): ?string
    {
        return "URLs.";
    }
}
