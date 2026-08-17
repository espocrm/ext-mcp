/**LICENSE**/

import FeatureRecordFieldsFieldView from 'modules/mcp/views/feature-record/fields/fields';

export default class FeatureRecordCreateWriteFieldsFieldView extends FeatureRecordFieldsFieldView {

    protected markRequired: boolean = true

    protected skipReadOnly: boolean = true

    protected skipReadOnlyAfterCreate: boolean = false
}
