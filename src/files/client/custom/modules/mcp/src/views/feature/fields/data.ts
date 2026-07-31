/**LICENSE**/

import BaseFieldView, {BaseOptions, BaseParams} from 'views/fields/base';
import Model from 'model';
import Utils from 'utils';
import FeatureRecordView from 'modules/mcp/views/feature/record/record';
import {PanelDefs} from 'views/record/detail';

export default class FeatureDataFieldView extends BaseFieldView {

    // language=Handlebars
    protected editTemplateContent = `
        <div data-role="sub">{{{sub}}}</div>
    `

    private subModel: Model | null = null

    private typeField: string = 'type'

    private metadataPath: string = 'app.mcpFeatures'

    private subView: FeatureRecordView | null = null

    getAttributeList() {
        return [
            ...super.getAttributeList(),
            this.typeField,
        ];
    }

    constructor(options: {[s: string]: unknown} & BaseOptions & BaseParams) {
        super(options);

        this.detailTemplateContent = this.editTemplateContent;
    }

    protected setup() {
        super.setup();

        this.validations.push(() => this.validateValid());

        this.model.onChange({
            attributes: [this.typeField],
            owner: this,
            callback: (o) => {
                if (!o.ui) {
                    return;
                }

                setTimeout(async () => {
                    await this.prepare();
                    await this.reRender();

                    this.trigger('change');
                });
            },
        });
    }

    protected async prepare() {
        this.subModel = null;

        this.clearView('sub');

        const type = this.model.attributes[this.typeField];

        if (!type) {
            return;
        }

        const params = this.getMetadata().get(`${this.metadataPath}.${type}.record`, {}) as {
            fields?: Record<string, any>,
            layout?: PanelDefs[],
            defaults?: Record<string, any>,
            client?: {viewSetupHandler?: string},
        };

        let initialValues = {};

        if (this.model.isNew()) {
            initialValues = Utils.cloneDeep(params.defaults ?? {});
        }

        this.subModel = new Model(initialValues, {
            defs: {fields: params.fields ?? {}},
        });

        const values = Espo.Utils.cloneDeep(this.model.attributes.data || {});

        this.subModel.setMultiple(values);

        this.subView = new FeatureRecordView({
            mode: this.mode === 'edit' ? 'edit' : 'detail',
            model: this.subModel,
            detailLayout: Utils.cloneDeep(params.layout ?? []),
            viewSetupHandler: params.client?.viewSetupHandler ?? null,
        });

        await this.assignView('sub', this.subView, `[data-name="sub"]`);
    }

    private validateValid(): boolean {
        if (!this.subView) {
            return false;
        }

        return this.subView.validate();
    }

    fetch(): Record<string, any> {
        if (!this.subModel) {
            return {};
        }

        return {
            [this.name]: Utils.cloneDeep(this.subModel.attributes),
        };
    }
}
