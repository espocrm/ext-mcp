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

import {inject} from 'di';
import ArrayFieldView from 'views/fields/array';
import Language from 'language';
import SetupHandler from 'modules/mcp/handlers/feature-record/setup';
import SchemaUtil from 'modules/mcp/helpers/schema-util';

export default class SetupReadHandler extends SetupHandler<{
    entityType: string | null,
}> {

    @inject(Language)
    private language: Language

    process() {
        if (!this.parentModel.isNew()) {
            this.view.setFieldReadOnly('entityType', true);
        }

        this.controlFields();

        this.model.onChange({
            attributes: ['entityType'],
            owner: this.view,
            callback: async (o) => {
                if (!o.ui) {
                    return;
                }

                await this.view.whenReady();

                this.controlFields();
            },
        });
    }

    private controlFields() {
        this.controlFieldsCommon('read', 'selectFields');
    }

    private controlFieldsCommon(type: 'read', field: string) {
        let fields: string[] = [];

        const entityType = this.model.attributes.entityType ?? null;

        if (entityType) {
            fields = new SchemaUtil().getFeatureFields({entityType, type});
        }

        this.view.setFieldOptionList(field, fields);

        const fieldView = this.view.getFieldView(field);

        if (!(fieldView instanceof ArrayFieldView)) {
            return;
        }

        const translations = this.getFieldTranslations(entityType, fields);

        fieldView.setTranslatedOptions(translations);
        fieldView.reRender();
    }

    private getFieldTranslations(entityType: string | null, fields: string[]): Record<string, string> {
        const translations: Record<string, string> = {};

        if (!entityType) {
            return {};
        }

        fields.forEach(field => {
            translations[field] = this.language.translate(field, 'fields', entityType);
        });

        return translations;
    }
}
