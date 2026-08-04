/**LICENSE**/

import type DetailRecordView from 'views/record/detail';
import Model from 'model';

export default abstract class SetupHandler<TModelSchema extends Record<string, any>> {

    protected readonly model: Model<TModelSchema>

    protected readonly parentModel: Model

    protected readonly view: DetailRecordView

    constructor(options: {
        view: DetailRecordView,
        parentModel: Model,
    }) {
        this.model = options.view.model as Model<TModelSchema>;
        this.parentModel = options.parentModel;

        this.view = options.view;
    }

    abstract process(): void;
}
