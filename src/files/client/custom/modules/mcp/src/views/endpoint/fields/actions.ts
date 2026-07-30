/**LICENSE**/

import ArrayFieldView from 'views/fields/array';

export default class EndpointActionsFieldView extends ArrayFieldView {

    protected setupOptions() {
        const translations: Record<string, string> = {};

        const scopes = this.getMetadata().getScopeObjectList()
            .sort((a, b) => {
                return this.translate(a, 'scopeNames')
                    .localeCompare(this.translate(b, 'scopeNames'))
            });

        const itemActions = [
            'list',
        ];

        const list: string[] = [];

        scopes.forEach(scope => {
            itemActions.forEach(itemAction => {
                const item = scope + '.' + itemAction;

                list.push(item);

                translations[item] =
                    this.translate(scope, 'scopeNames') + ' . ' +
                    this.translate(itemAction, 'itemActions', 'Mcp');
            })
        });

        this.translatedOptions = translations;
        this.params.options = list;
    }
}
