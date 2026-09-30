<?php
/**LICENSE**/

namespace Espo\Modules\Mcp\Tools\Feature\RecordStream;

use Espo\Core\Utils\Metadata;
use Espo\Modules\Mcp\Tools\Feature\Data;
use Espo\Modules\Mcp\Tools\Feature\DataValidator;
use Espo\Modules\Mcp\Tools\Feature\DataValidator\Failure;

/**
 * @implements DataValidator<RecordStreamData>
 */
class RecordStreamDataValidator implements DataValidator
{
    public function __construct(
        private Metadata $metadata,
    ) {}

    public function validate(Data $data): array
    {
        $list = [];

        foreach ($data->entityTypes as $entityType) {
            if (!$this->isEntityTypeValid($entityType)) {
                $list[] = new Failure(
                    field: 'entityTypes',
                    message: "Not allowed entity type '$entityType'.",
                );
            }
        }

        return $list;
    }

    private function isEntityTypeValid(string $entityType): bool
    {
        $defs = $this->metadata->get("scopes.$entityType");

        if (!is_array($defs)) {
            return false;
        }

        $isEntity = $defs['entity'] ?? false;
        $isObject = $defs['object'] ?? false;
        $hasStream = $defs['stream'] ?? false;

        return
            $isEntity &&
            $isObject &&
            $hasStream;
    }
}
