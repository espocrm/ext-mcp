<?php
/**LICENSE**/

namespace Espo\Modules\Mcp\Classes\FieldValidators\McpEndpoint\Actions;

use Espo\Core\FieldValidation\Validator;
use Espo\Core\FieldValidation\Validator\Data;
use Espo\Core\FieldValidation\Validator\Failure;
use Espo\Core\Utils\Metadata;
use Espo\Modules\Mcp\Entities\McpEndpoint;
use Espo\Modules\Mcp\Tools\Mcp\RecordItemAction;
use Espo\ORM\Entity;

/**
 * @implements Validator<McpEndpoint>
 */
class Valid implements Validator
{
    /**
     * @var string[]
     */
    private array $itemActions = [
        RecordItemAction::LIST,
    ];

    public function __construct(
        private Metadata $metadata,
    ) {}


    public function validate(Entity $entity, string $field, Data $data): ?Failure
    {
        foreach ($entity->getActions() as $action) {
            $itemResult = $this->validateActionItem($action);

            if ($itemResult) {
                return $itemResult;
            }
        }

        return null;
    }

    private function validateActionItem(string $action): ?Failure
    {
        if (substr_count($action, '.') === 1) {
            [$scope, $itemAction] = explode('.', $action);

            if (
                !$this->metadata->get("scopes.$scope.object") ||
                !$this->metadata->get("scopes.$scope.entity")
            ) {
                return Failure::create();
            }

            if (!in_array($itemAction, $this->itemActions)) {
                return Failure::create();
            }

            return null;
        }

        return Failure::create();
    }
}
