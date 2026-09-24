<?php
/**LICENSE**/

namespace Espo\Modules\Mcp\Tools\Feature\Utils;

use Espo\Core\Utils\Metadata;

class DataValidatorHelper
{
    public function __construct(
        private Metadata $metadata,
    ) {}

    public function isObjectEntityType(string $scope): bool
    {
        return
            $this->metadata->get("scopes.$scope.object") &&
            $this->metadata->get("scopes.$scope.entity");
    }
}
