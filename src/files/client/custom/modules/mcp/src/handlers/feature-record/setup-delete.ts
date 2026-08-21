/**LICENSE**/

import {inject} from 'di';
import Language from 'language';
import SetupHandler from 'modules/mcp/handlers/feature-record/setup';

export default class SetupUpdateHandler extends SetupHandler<{
    entityType: string | null,
}> {

    @inject(Language)
    private language: Language

    process() {
        if (!this.parentModel.isNew()) {
            this.view.setFieldReadOnly('entityType', true);
        }
    }
}
