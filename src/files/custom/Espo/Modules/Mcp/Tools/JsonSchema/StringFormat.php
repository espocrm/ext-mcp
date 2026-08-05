<?php
/**LICENSE**/

namespace Espo\Modules\Mcp\Tools\JsonSchema;

enum StringFormat: string
{
    case dateTime = 'date-time';
    case date = 'date';
    case duration = 'duration';
    case uuid = 'uuid';
    case uri = 'uri';
    case email = 'email';
    case regex = 'regex';
    case idnEmail = 'idn-email';
    case hostname = 'hostname';
    case idnHostname = 'idn-hostname';
}
