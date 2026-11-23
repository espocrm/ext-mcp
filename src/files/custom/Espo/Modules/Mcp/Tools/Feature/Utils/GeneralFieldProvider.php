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

namespace Espo\Modules\Mcp\Tools\Feature\Utils;

use Espo\Core\Utils\Metadata;
use Espo\Modules\Mcp\Tools\Feature\Features\Find\FindData\Field;

class GeneralFieldProvider
{
    public const string ACTION_FILTER = 'filter';
    public const string ACTION_SELECT = 'select';
    public const string ACTION_READ = 'read';
    public const string ACTION_CREATE = 'create';
    public const string ACTION_UPDATE = 'update';

    public function __construct(
        private Metadata $metadata,
    ) {}

    /**
     * @param Field[] $fields
     * @return Field[]
     */
    public function get(string $entityType, string $action, array $fields): array
    {
        $mandatory = $this->getMandatory($entityType, $action);

        $fields = array_filter($fields, function ($it) use ($entityType, $action) {
            $field = $it->name;

            return $this->metadata->get("app.mcpScopes.$entityType.fields.$field.$action") !== false;
        });

        $fields = array_values($fields);

        return self::append($mandatory, $fields);
    }

    /**
     * @return string[]
     */
    private function getMandatory(string $entityType, string $action): array
    {
        /**
         * @var array<string, array{
         *     mandatory?: bool,
         *     filter?: bool|'mandatory',
         *     select?: bool|'mandatory',
         *     read?: bool|'mandatory',
         *     create?: bool|'mandatory',
         *     update?: bool|'mandatory',
         * }> $fieldsDefs
         */
        $fieldsDefs = $this->metadata->get("app.mcpScopes.$entityType.fields") ?? [];

        $output = [];

        foreach ($fieldsDefs as $field => $defs) {
            if (
                !$this->metadata->get("entityDefs.$entityType.fields.$field") ||
                $this->metadata->get("entityDefs.$entityType.fields.$field.disabled")
            ) {
                continue;
            }

            $level = $defs[$action] ?? null;

            if (
                (($defs['mandatory'] ?? false) && $level !== false) ||
                $level === 'mandatory'
            ) {
                $output[] = $field;
            }
        }

        return $output;
    }

    /**
     * @param string[] $mandatory
     * @param Field[] $fields
     * @return Field[]
     */
    private static function append(array $mandatory, array $fields): array
    {
        $met = array_fill_keys($mandatory, true);

        $fields = array_filter($fields, fn ($it) => !isset($met[$it->name]));
        $fields = array_values($fields);

        return array_merge(
            array_map(fn ($it) => new Field($it), $mandatory),
            $fields,
        );
    }
}
