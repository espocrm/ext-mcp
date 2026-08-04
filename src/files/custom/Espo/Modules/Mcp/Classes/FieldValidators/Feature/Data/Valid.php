<?php
/**LICENSE**/

namespace Espo\Modules\Mcp\Classes\FieldValidators\Feature\Data;

use Espo\Core\FieldValidation\Validator;
use Espo\Core\FieldValidation\Validator\Data;
use Espo\Core\FieldValidation\Validator\Failure;
use Espo\Core\Utils\Log;
use Espo\Modules\Mcp\Entities\Feature;
use Espo\Modules\Mcp\Tools\Feature\DataFactory;
use Espo\Modules\Mcp\Tools\Feature\Exceptions\BadFeatureData;
use Espo\Modules\Mcp\Tools\Feature\Exceptions\UnsupportedType;
use Espo\ORM\Entity;

/**
 * @implements Validator<Feature>
 */
class Valid implements Validator
{
    public function __construct(
        private DataFactory $factory,
        private Log $log,
    ) {}

    public function validate(Entity $entity, string $field, Data $data): ?Failure
    {
        if (!$entity->has(Feature::FIELD_TYPE)) {
            return null;
        }

        try {
            $this->factory->createForFeature($entity);
        } catch (BadFeatureData|UnsupportedType $e) {
            $this->log->info("Invalid data.", ['exception' => $e]);

            return Failure::create();
        }

        return null;
    }
}
