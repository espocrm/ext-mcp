<?php
/**LICENSE**/

namespace Espo\Modules\Mcp\Tools\Feature\Update;

use Espo\Core\Utils\Language;
use Espo\Modules\Mcp\Tools\Feature\Data;
use Espo\Modules\Mcp\Tools\Feature\TextComposer;

/**
 * @implements TextComposer<UpdateData>
 */
class UpdateTextComposer implements TextComposer
{
    public function __construct(
        private Language $language,
    ) {}

    public function compose(Data $data): string
    {
        return $this->language->translateLabel($data->entityType, 'scopeNames');
    }
}
