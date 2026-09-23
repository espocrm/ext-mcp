<?php
/************************************************************************
* This file is part of MCP extension for EspoCRM.
*
* MCP extension for EspoCRM.
* Copyright (C) 2026 EspoCRM, Inc.
* Website: https://www.espocrm.com
*
* This program is free software: you can redistribute it and/or modify
* it under the terms of the GNU Affero General Public License as published by
* the Free Software Foundation, either version 3 of the License, or
* (at your option) any later version.
*
* This program is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
* GNU Affero General Public License for more details.
*
* You should have received a copy of the GNU Affero General Public License
* along with this program. If not, see <https://www.gnu.org/licenses/>.
*
* The interactive user interfaces in modified source and object code versions
* of this program must display Appropriate Legal Notices, as required under
* Section 5 of the GNU Affero General Public License version 3.
*
* In accordance with Section 7(b) of the GNU Affero General Public License version 3,
* these Appropriate Legal Notices must retain the display of the "EspoCRM" word.
************************************************************************/

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
            $description .= " If set, must be earlier than the `$before` field.";
        }

        if ($after) {
            if ($fieldDefs->getParam('afterOrEqual')) {
                $description = " If set, must be later than the `$after` field or equal to it.";
            } else {
                $description = " If set, must be later than the `$after` field.";
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
