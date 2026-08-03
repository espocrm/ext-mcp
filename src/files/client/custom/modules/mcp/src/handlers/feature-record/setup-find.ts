/**LICENSE**/

import type DetailRecordView from 'views/record/detail';
import Metadata from 'metadata';
import {inject} from 'di';
import Model from 'model';
import FieldManager from 'field-manager';
import ArrayFieldView from 'views/fields/array';
import Language from 'language';

export default class SetupFindHandler {

    @inject(Metadata)
    private metadata: Metadata

    @inject(FieldManager)
    private fieldManager: FieldManager

    @inject(Language)
    private language: Language

    private model: Model<{
        entityType: string | null,
    }>

    constructor(private view: DetailRecordView) {
        this.model = this.view.model!;
    }

    process() {
        // @todo Entity type readonly if any field is added.

        this.controlSelectFields();
        this.controlFilterFields();

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
            },
        });
    }

    private controlSelectFields() {
        this.controlFields('select', 'selectFields');
    }

    private controlFilterFields() {
        this.controlFields('filter', 'filterFields');
    }

    private controlFields(type: 'filter' | 'select', field: string) {
        let fields: string[] = [];

        const entityType = this.model.attributes.entityType ?? null;

        if (entityType) {
            const fieldTypes = this.metadata.get(`app.mcpSchema.fieldTypes`, {}) as
                Record<string, {filter?: boolean, select?: boolean}>;

            const types = Object.keys(fieldTypes).filter(it => fieldTypes[it][type]);

            fields = this.fieldManager.getEntityTypeFieldList(entityType, {
                onlyAvailable: true,
                typeList: types,
            });
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
