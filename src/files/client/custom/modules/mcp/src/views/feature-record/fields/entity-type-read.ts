/**LICENSE**/

import FeatureRecordEntityTypeFieldView from 'modules/mcp/views/feature-record/fields/entity-type';

export default class FeatureRecordEntityTypeReadFieldView extends FeatureRecordEntityTypeFieldView {

    protected allowedScopes: string[] = [
        'Team',
        'User',
    ]
}
