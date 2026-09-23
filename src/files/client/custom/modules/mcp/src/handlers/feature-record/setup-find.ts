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

import Metadata from 'metadata';
import {inject} from 'di';
import ArrayFieldView from 'views/fields/array';
import Language from 'language';
import SetupHandler from 'modules/mcp/handlers/feature-record/setup';
import SchemaUtil from 'modules/mcp/helpers/schema-util';

export default class SetupFindHandler extends SetupHandler<{
    entityType: string | null,
}> {

    @inject(Metadata)
    private metadata: Metadata

    @inject(Language)
    private language: Language

    process() {
        if (!this.parentModel.isNew()) {
            this.view.setFieldReadOnly('entityType', true);
        }

        this.controlSelectFields();
        this.controlFilterFields();
        this.controlPrimaryFilters();
        this.controlBoolFilters();

        this.model.onChange({
            attributes: ['entityType'],
            owner: this.view,
            callback: async (o) => {
                if (!o.ui) {
                    return;
                }

                await this.view.whenReady();

                this.controlSelectFields();
                this.controlFilterFields();
                this.controlPrimaryFilters();
                this.controlBoolFilters();
            },
        });
    }

    private controlPrimaryFilters() {
        let filters: string[] = [];

        const entityType = this.model.attributes.entityType ?? null;

        if (entityType) {
            const allFilters = this.metadata.get(`clientDefs.${entityType}.filterList`, []) as
                ({name: string, aux?: boolean} | string)[];

            filters = allFilters.map(it => {
                if (typeof it === 'string') {
                    return it;
                }

                return it.name;
            });

            if (this.metadata.get(`scopes.${entityType}.stars`)) {
                filters.push('starred')
            }
        }

        this.view.setFieldOptionList('primaryFilters', filters);

        const fieldView = this.view.getFieldView('primaryFilters');

        if (!(fieldView instanceof ArrayFieldView)) {
            return;
        }

        const translations: Record<string, string> = {};

        filters.forEach(it => {
            translations[it] = this.language.translate(it, 'presetFilters', entityType);
        });

        fieldView.setTranslatedOptions(translations);
        fieldView.reRender();
    }

    private controlBoolFilters() {
        let filters: string[] = [];

        const entityType = this.model.attributes.entityType ?? null;

        if (entityType) {
            const allFilters = this.metadata.get(`clientDefs.${entityType}.boolFilterList`, []) as
                ({name: string, aux?: boolean} | string)[];

            filters = allFilters.map(it => {
                if (typeof it === 'string') {
                    return it;
                }

                return it.name;
            });
        }

        this.view.setFieldOptionList('boolFilters', filters);

        const fieldView = this.view.getFieldView('boolFilters');

        if (!(fieldView instanceof ArrayFieldView)) {
            return;
        }

        const translations: Record<string, string> = {};

        filters.forEach(it => {
            translations[it] = this.language.translate(it, 'boolFilters', entityType);
        });

        fieldView.setTranslatedOptions(translations);
        fieldView.reRender();
    }

    private controlSelectFields() {
        this.controlFieldsCommon('select', 'selectFields');
    }

    private controlFilterFields() {
        // @todo Support override in entityDefs, to be able to enable custom fields.
        this.controlFieldsCommon('filter', 'filterFields');
    }

    private controlFieldsCommon(type: 'filter' | 'select', field: string) {
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
