/**LICENSE**/

import View from 'view';
import Model from 'model';
import DetailRecordView, {PanelDefs} from 'views/record/detail';
import EditRecordView, {EditRecordViewOptions} from 'views/record/edit';

export default class FeatureRecordView extends View<{
    options: {
        mode: 'detail' | 'edit',
        model: Model,
        detailLayout: PanelDefs[],
        viewSetupHandler: string | null,
    }
}> {

    // language=Handlebars
    protected templateContent = `
        <!--suppress CssUnusedSymbol -->
        <style>
            [data-role="sub-record"] {
                .panel {
                    box-shadow: none;
                }
            }
        </style>
        <div data-role="sub-record">{{{record}}}</div>
    `

    private recordView: DetailRecordView

    protected setup() {
        const options = {
            model: this.model,
            detailLayout: this.options.detailLayout,
            buttonsDisabled: true,
            sideView: null,
            bottomView: null,
            isWide: true,
            shortcutKeysEnabled: true,
        } as EditRecordViewOptions;

        if (this.options.mode === 'edit') {
            this.recordView = new EditRecordView(options);
        } else {
            options.readOnly = true;

            this.recordView = new DetailRecordView(options);
        }

        this.assignView('record', this.recordView, '[data-role="sub-record"]');


        let handler: {process: () => {}} | null = null;

        if (this.options.viewSetupHandler) {
            this.wait(
                (async () => {
                    const Handler = await Espo.loader.requirePromise(this.options.viewSetupHandler!) as any;

                    handler = new Handler(this.recordView);
                })()
            )
        }

        this.whenReady().then(() => {
            handler?.process();
        });
    }

    validate(): boolean {
        return this.recordView.validate();
    }
}
