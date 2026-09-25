/**LICENSE**/

import ArrayFieldView from 'views/fields/array';

export default class RecordStreamEntityTypesFieldView extends ArrayFieldView {

    setupOptions() {
        this.params.translation = 'Global.scopeNames';

        super.setupOptions();

        const scopes = this.getMetadata().getScopeEntityList();

        this.params.options = scopes
            .filter(scope => {
                const defs = this.getMetadata().get(`scopes.${scope}`, {}) as Record<string, any>;

                if (
                    !defs.entity ||
                    !defs.object ||
                    !defs.stream ||
                    defs.disabled
                ) {
                    return false;
                }

                return true;
            })
            .sort((a, b) => {
                return this.translate(a, 'scopeNames').localeCompare(this.translate(b, 'scopeNames'));
            });
    }
}
