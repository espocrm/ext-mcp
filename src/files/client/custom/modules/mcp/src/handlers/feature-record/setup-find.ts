/**LICENSE**/

import type DetailRecordView from 'views/record/detail';

export default class SetupFindHandler {

    constructor(private view: DetailRecordView) {}

    process() {
        console.log(this.view);
    }
}
