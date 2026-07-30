<?php
/**LICENSE**/

namespace Espo\Modules\Mcp\Tools\Mcp;

class SupportedVersionsProvider
{
    /**
     * @var string[]
     */
    private array $versions = [
        '2026-07-28',
    ];

    /**
     * @return string[]
     */
    public function get(): array
    {
        return $this->versions;
    }
}
