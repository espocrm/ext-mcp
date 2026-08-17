<?php
/**LICENSE**/

namespace Espo\Modules\Mcp\Tools\Mcp\Schema\Elicitation;

enum StringFormat: string
{
    case Uri = 'uri';
    case Email = 'email';
    case Date = 'date';
    case DateTime = 'date-time';
}
