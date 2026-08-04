<?php
/**LICENSE**/

namespace Espo\Modules\Mcp\Classes\FieldLoaders\Feature;

use Espo\Core\FieldProcessing\Loader;
use Espo\Core\FieldProcessing\Loader\Params;
use Espo\Modules\Mcp\Entities\Feature;
use Espo\Modules\Mcp\Tools\Feature\DataFactory;
use Espo\Modules\Mcp\Tools\Feature\Exceptions\BadFeatureData;
use Espo\Modules\Mcp\Tools\Feature\Exceptions\UnsupportedType;
use Espo\Modules\Mcp\Tools\Feature\TextComposerFactory;
use Espo\ORM\Entity;

/**
 * @implements Loader<Feature>
 */
class TextLoader implements Loader
{
    public function __construct(
        private DataFactory $dataFactory,
        private TextComposerFactory $textComposerFactory,
    ) {}

    public function process(Entity $entity, Params $params): void
    {
        try {
            $data = $this->dataFactory->createForFeature($entity);
        } catch (BadFeatureData|UnsupportedType) {
            return;
        }

        try {
            $textComposer = $this->textComposerFactory->create($entity->getType());
        } catch (UnsupportedType) {
            return;
        }

        $text = $textComposer->compose($data);

        $entity->set(Feature::FIELD_TEXT, $text);
    }
}
