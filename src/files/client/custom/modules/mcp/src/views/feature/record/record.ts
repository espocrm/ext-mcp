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

import View from 'view';
import Model from 'model';
import DetailRecordView, {PanelDefs} from 'views/record/detail';
import EditRecordView, {EditRecordViewOptions} from 'views/record/edit';
import {Defs} from 'dynamic-logic';

export default class FeatureRecordView extends View<{
    options: {
        mode: 'detail' | 'edit',
        model: Model,
        parentModel: Model,
        detailLayout: PanelDefs[],
        viewSetupHandler: string | null,
        fieldsDynamicLogic: Defs['fields'] | null,
    }
}> {

    // language=Handlebars
    protected templateContent = `
        <!--suppress CssUnusedSymbol -->
        <style>
            [data-role="sub-record"] {
                .panel {
                    box-shadow: none;
                    border: 0;

                    > .panel-body {
                        > .row {
                            margin-left: var(--grid-gutter-width-half) !important;
                            margin-right: var(--grid-gutter-width-half) !important;

                            > div {
                                padding-left: var(--grid-gutter-width-half) !important;
                                padding-right: var(--grid-gutter-width-half) !important;
                            }
                        }
                    }
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
            dynamicLogicDefs: {
                fields: this.options.fieldsDynamicLogic ?? {},
            },
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

                    handler = new Handler({
                        view: this.recordView,
                        parentModel: this.options.parentModel,
                    });
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
