<?php
/**LICENSE**/

namespace Espo\Modules\Mcp\Tools\Schema\Field\FieldSchemaProvider;

readonly class Params
{
    public function __construct(
        public string $entityType,
        public string $field,
        public Action $action,
    ) {}

    public function isWriteAction(): bool
    {
        return $this->action === Action::Update || $this->action === Action::Create;
    }
}
