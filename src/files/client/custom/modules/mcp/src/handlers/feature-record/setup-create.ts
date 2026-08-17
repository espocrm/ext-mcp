/**LICENSE**/

import {inject} from 'di';
import ArrayFieldView from 'views/fields/array';
import Language from 'language';
import SetupHandler from 'modules/mcp/handlers/feature-record/setup';
import SchemaUtil from 'modules/mcp/helpers/schema-util';

export default class SetupCreateHandler extends SetupHandler<{
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
        this.controlFieldsCommon('create', 'writeFields');
    }

    private controlFieldsCommon(type: 'create', field: string) {
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
