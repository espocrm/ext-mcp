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

import EnumFieldView from 'views/fields/enum';
import type FeatureDataModel from 'modules/mcp/models/other/feature-data';

export default class FeatureRecordEntityTypeFieldView extends EnumFieldView<{
    model: FeatureDataModel,
}> {

    setupOptions() {
        this.params.translation = 'Global.scopeNames';

        super.setupOptions();

        const type = this.model.featureModel.attributes.type!;

        const scopes = this.getMetadata().getScopeEntityList();

        this.params.options = scopes
            .filter(scope => {
                const defs = this.getMetadata().get(`scopes.${scope}`, {}) as Record<string, any>;

                if (!defs.entity || defs.disabled) {
                    return false;
                }

                const override = this.getMetadata().get(`app.mcpScopes.${scope}.features.${type}`);

                if (override != null) {
                    return override;
                }

                return !!defs.object;
            })
            .sort((a, b) => {
                return this.translate(a, 'scopeNames').localeCompare(this.translate(b, 'scopeNames'));
            });

        this.params.options.unshift('');
    }
}
