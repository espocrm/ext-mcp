<?php
/**LICENSE**/

namespace Espo\Modules\Mcp\Tools\Mcp\Tool;

use Espo\Modules\Mcp\Tools\Mcp\Schema\Tool\Tool;

readonly class ToolEnvelope
{
    public function __construct(
        public Tool $tool,
        public string $featureId,
    ) {}
}
