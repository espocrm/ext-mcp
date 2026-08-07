<?php
/**LICENSE**/

namespace Espo\Modules\Mcp\Tools\JsonSchema;

use JsonSerializable;

interface Schema extends JsonSerializable
{
    public function getTitle(): ?string;

    public function withTitle(?string $title): static;

    public function getDescription(): ?string;

    public function withDescription(?string $description): static;
}
