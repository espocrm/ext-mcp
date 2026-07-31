/**LICENSE**/

import EntityTypeFieldView from 'views/fields/entity-type';

export default class FeatureRecordEntityTypeFieldView extends EntityTypeFieldView {

    setupOptions() {
        super.setupOptions();

        this.params.options = (this.params.options ?? []).filter(scope => {
            if (scope === '') {
                return true;
            }

            const defs = this.getMetadata().get(`scopes.${scope}`, {}) as Record<string, any>;

            return !!defs.object;
        });
    }
}
