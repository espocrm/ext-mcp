<?php
/**LICENSE**/

namespace Espo\Modules\Mcp\Tools\Schema\Field\FieldSchemaProvider;

enum Action
{
    case Find;
    case Read;
    case Create;
    case Update;
}
