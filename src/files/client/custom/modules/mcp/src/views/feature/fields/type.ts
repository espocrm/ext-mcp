/**LICENSE**/

import EnumFieldView from 'views/fields/enum';

export default class FeatureTypeFieldView extends EnumFieldView {

    protected setupOptions() {
        const defs = this.getMetadata().get('app.mcpFeatures', {}) as Record<string, any>;

        this.params.options = Object.keys(defs);
        this.params.options.unshift('');
    }
}
