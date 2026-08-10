<?php
/**LICENSE**/

namespace Espo\Modules\Mcp\Tools\JsonSchema;

enum GroupKeyword: string
{
    case anyOf = 'anyOf';
    case allOf = 'allOf';
    case oneOf = 'oneOf';
}
