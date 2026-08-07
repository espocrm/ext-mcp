<?php
/**LICENSE**/

namespace Espo\Modules\Mcp\Tools\Schema\Field;

use Espo\ORM\Defs\FieldDefs;

class DateHelper
{
    public function prepareDescription(FieldDefs $fieldDefs): ?string
    {
        $description = '';

        $before = $fieldDefs->getParam('before');
        $after = $fieldDefs->getParam('after');

        if ($before) {
            $description .= " If set, must be earlier than `$before` field.";
        }

        if ($after) {
            if ($fieldDefs->getParam('afterOrEqual')) {
                $description = " If set, must be later than `$after` field or equal to it.";
            } else {
                $description = " If set, must be later than `$after` field.";
            }
        }

        if ($description) {
            $description = trim($description);
        } else {
            $description = null;
        }

        return $description;
    }
}
