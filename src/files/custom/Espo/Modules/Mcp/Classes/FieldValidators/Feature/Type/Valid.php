<?php
/**LICENSE**/

namespace Espo\Modules\Mcp\Classes\FieldValidators\Feature\Type;

use Espo\Core\FieldValidation\Validator;
use Espo\Core\FieldValidation\Validator\Data;
use Espo\Core\FieldValidation\Validator\Failure;
use Espo\Core\Utils\Metadata;
use Espo\Modules\Mcp\Entities\Feature;
use Espo\ORM\Entity;
use Throwable;

/**
 * @implements Validator<Feature>
 */
class Valid implements Validator
{
    public function __construct(
        private Metadata $metadata,
    ) {}

    public function validate(Entity $entity, string $field, Data $data): ?Failure
    {
        try {
            $type = $entity->getType();
        } catch (Throwable) {
            return null;
        }

        if (!$this->metadata->get("app.mcpFeatures.$type")) {
            return Failure::create();
        }

        return null;
    }
}
