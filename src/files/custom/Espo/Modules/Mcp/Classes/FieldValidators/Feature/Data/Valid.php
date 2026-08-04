<?php
/**LICENSE**/

namespace Espo\Modules\Mcp\Classes\FieldValidators\Feature\Data;

use Espo\Core\FieldValidation\Validator;
use Espo\Core\FieldValidation\Validator\Data;
use Espo\Core\FieldValidation\Validator\Failure;
use Espo\Core\Utils\Log;
use Espo\Modules\Mcp\Entities\Feature;
use Espo\Modules\Mcp\Tools\Feature\DataFactory;
use Espo\Modules\Mcp\Tools\Feature\DataValidator;
use Espo\Modules\Mcp\Tools\Feature\DataValidatorFactory;
use Espo\Modules\Mcp\Tools\Feature\Exceptions\BadFeatureData;
use Espo\Modules\Mcp\Tools\Feature\Exceptions\UnsupportedType;
use Espo\Modules\Mcp\Tools\Feature\Validator\Failure as ValidatorFailure;
use Espo\ORM\Entity;

/**
 * @implements Validator<Feature>
 */
class Valid implements Validator
{
    public function __construct(
        private DataFactory $factory,
        private DataValidatorFactory $dataValidatorFactory,
        private Log $log,
    ) {}

    public function validate(Entity $entity, string $field, Data $data): ?Failure
    {
        if (!$entity->has(Feature::FIELD_TYPE)) {
            return null;
        }

        try {
            $recordData = $this->factory->createForFeature($entity);
        } catch (BadFeatureData|UnsupportedType $e) {
            $this->log->info("Invalid data.", ['exception' => $e]);

            return Failure::create();
        }

        try {
            $dataValidator = $this->dataValidatorFactory->create($entity->getType());
        } catch (UnsupportedType) {
            return null;
        }

        $failures = $dataValidator->validate($recordData);

        if ($failures === []) {
            return null;
        }

        $messages = array_map(fn (ValidatorFailure $it) => $it->message ?? $it->field, $failures);

        $this->log->info("Invalid data. {messages}", [
            'messages' => implode(' ', $messages),
        ]);

        return Failure::create();
    }
}
