<?php
/**LICENSE**/

namespace Espo\Modules\Mcp\Tools\Mcp\Schema\Value;

enum CacheScope: string
{
    case Public = 'public';
    case Private = 'private';
}
