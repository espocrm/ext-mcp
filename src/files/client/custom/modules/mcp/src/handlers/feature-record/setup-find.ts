/**LICENSE**/

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
