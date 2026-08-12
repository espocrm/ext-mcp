<?php
/**LICENSE**/

namespace Espo\Modules\Mcp\Tools\Feature\Find;

use Espo\Core\Utils\Language;
use Espo\Modules\Mcp\Entities\Feature;
use Espo\Modules\Mcp\Tools\Feature\Data;
use Espo\Modules\Mcp\Tools\Feature\TextComposer;

/**
 * @implements TextComposer<FindData>
 */
class FindTextComposer implements TextComposer
{
    public function __construct(
        private Language $language,
    ) {}

    public function compose(Data $data): string
    {
        $entityTypeLabel = $this->language->translateLabel($data->entityType, 'scopeNames');

        $type = $this->language->translateOption(FindData::TYPE, Feature::FIELD_TYPE, Feature::ENTITY_TYPE);

        return $entityTypeLabel;
    }
}
