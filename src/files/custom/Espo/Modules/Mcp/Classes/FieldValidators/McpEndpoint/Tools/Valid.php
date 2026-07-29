<?php
/**LICENSE**/

namespace Espo\Modules\Mcp\Classes\FieldValidators\McpEndpoint\Tools;

use Espo\Core\FieldValidation\Validator;
use Espo\Core\FieldValidation\Validator\Data;
use Espo\Core\FieldValidation\Validator\Failure;
use Espo\Modules\Mcp\Entities\McpEndpoint;
use Espo\ORM\Entity;

/**
 * @implements Validator<McpEndpoint>
 */
class Valid implements Validator
{
    public function validate(Entity $entity, string $field, Data $data): ?Failure
    {
        // TODO: Implement validate() method.
    }
}
