/**LICENSE**/

import FeatureRecordFieldsFieldView from 'modules/mcp/views/feature-record/fields/fields';

export default class FeatureRecordUpdateWriteFieldsFieldView extends FeatureRecordFieldsFieldView {

    protected skipReadOnly: boolean = true

    protected skipReadOnlyAfterCreate: boolean = true
}
