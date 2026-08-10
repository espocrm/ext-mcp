/**LICENSE**/

import BaseFieldView from 'views/fields/base';
import Collection from 'collection';
import Model from 'model';
import View from 'view';
import TextFieldView from 'views/fields/text';
import ArrayFieldAdd from 'views/modals/array-field-add';

interface ItemSchema {
    name: string;
    description: string | null;
}

export default class FeatureRecordSelectFieldsFieldView extends BaseFieldView<{
    model: Model<Record<string, any> & {
        targetEntityType: string | null,
    }>
}> {

    // language=Handlebars
    protected detailTemplateContent = `
        {{#if items.length}}
            <table class="table" data-role="item-list">
                {{#each items}}
                    <tr data-name="{{name}}"><td>{{{lookup ../this key}}}</td></div>
                {{/each}}
            </table>
        {{/if}}

        {{#unless items.length}}
            {{#if isSet}}
                <span class="none-value">{{translate 'None'}}</span>
            {{else}}
                <span class="loading-value"></span>
            {{/if}}
        {{/unless}}
    `

    // language=Handlebars
    protected editTemplateContent = `
        {{#each items}}
            <div data-id="{{name}}" data-role="item-list">{{{lookup ../this key}}}</div>
        {{/each}}

        <div>
            <button
                class="btn btn-default btn-icon"
                data-action="add"
                title="{{translate 'Add'}}"
            ><span class="fas fa-plus"></span></button>
        </div>
    `

    private itemCollection: Collection<Model<ItemSchema>> | null = null

    private itemViews: ItemView[]

    protected data(): Record<string, any> {
        return {
            isSet: this.model.has(this.name),
            items: this.itemCollection?.models.map(m => ({
                name: m.attributes.name,
                key: this.getItemKey(m),
            })),
        };
    }

    private getItemKey(model: Model<ItemSchema>): string {
        return model.attributes.name + 'Field';
    }

    /**
     * Prevents the change event from firing on sub-field change.
     */
    protected initElement() {}

    protected setup() {
        this.addActionHandler('addRow', () => this.addRow());

        this.validations.push(() => this.validateItems());
        this.validations.push(() => this.validateRequired());
    }

    private validateItems(): boolean {
        if (!this.itemViews) {
            return false;
        }

        let isNotValid = false;

        this.itemViews.forEach(view => {
            if (view.validate()) {
                isNotValid = true;
            }
        });

        return isNotValid;
    }

    validateRequired(): boolean {
        if (!this.params.required) {
            return false;
        }

        const items = this.model.attributes[this.name] as Record<string, any>[] | undefined;

        if (items && items.length) {
            return false;
        }

        const message = this.translate('fieldIsRequired', 'messages')
            .replace('{field}', this.getLabelText());

        this.showValidationMessage(message, '[data-action="add"]');

        return true;
    }

    protected async prepare() {
        this.destroyItemViews();

        this.itemCollection = new Collection<Model<ItemSchema>>();

        const items = this.getItemsFromModel();

        if (items === undefined) {
            return;
        }

        let mode = this.mode;

        if (mode !== 'detail' && mode !== 'edit') {
            mode = 'detail';
        }

        const promiseList = [];
        this.itemViews = [];

        for (const [i, item] of items.entries()) {
            const model = new Model({...item});

            model.onChange({
                owner: this,
                callback: o => {
                    if (o.ui) {
                        this.model.setMultiple({
                            [this.name]: this.getItemsFromCollection(),
                        }, {ui: true});
                    }
                },
            });

            this.itemCollection.push(model);

            const view = new ItemView({
                model: model,
                mode: mode === 'edit' ? 'edit' : 'detail',
                onRemove: () => this.removeRow(i),
                targetEntityType: this.model.attributes.targetEntityType ?? null,
            });

            this.itemViews.push(view);

            const key = this.getItemKey(model);

            const promise = this.assignView(key, view, `[data-name="${model.attributes.name}"]`);

            promiseList.push(promise);
        }

        await Promise.all(promiseList);
    }

    private destroyItemViews() {
        this.itemViews = [];

        this.itemCollection?.models.forEach(model => this.clearView(model.id!));
    }

    private getItemsFromModel(): ItemSchema[] | undefined {
        return Espo.Utils.cloneDeep(this.model.attributes[this.name]) as ItemSchema[] | undefined;
    }

    private getItemsFromCollection(): ItemSchema[] {
        if (!this.itemCollection) {
            return [];
        }

        return this.itemCollection.models.map(item => {
            return {
                name: item.attributes.name!,
                description: item.attributes.description ?? null,
            };
        });
    }

    private async addRow() {
        const items = this.getItemsFromModel() ?? [];

        const currentFields = this.getItemsFromModel()?.map(it => it.name) ?? [];

        const targetEntityType = this.model.attributes.targetEntityType;

        if (!targetEntityType) {
            throw new Error();
        }

        const fields = (this.recordHelper?.getFieldOptionList(this.name) ?? [])
            .filter(it => !currentFields.includes(it));

        const view = new ArrayFieldAdd({
            options: fields,
            translation: this.getFieldTranslations(targetEntityType, fields),
        });

        await this.assignView('dialog', view);
        await view.render();

        this.listenTo(view, 'add-mass', (names: string[]) => add(names));
        this.listenTo(view, 'add', (name: string) => add([name]));

        const add = async (names: string[]) => {
            names.forEach(name => {
                items.push({
                    name: name,
                    description: null,
                });
            });

            this.model.setMultiple({
                [this.name]: items,
            }, {ui: true});

            await this.prepare();
            await this.reRender();
        };
    }

    private async removeRow(index: number) {
        const items = this.getItemsFromModel() ?? [];

        items.splice(index, 1);

        this.model.setMultiple({items}, {ui: true});

        await this.prepare();
        await this.reRender();
    }

    private getFieldTranslations(entityType: string, fields: string[]): Record<string, string> {
        const translations: Record<string, string> = {};

        fields.forEach(field => {
            translations[field] = this.getLanguage().translate(field, 'fields', entityType);
        });

        return translations;
    }

    fetch(): Record<string, any> {
        const items = this.getItemsFromCollection().map(item => {
            return {
                name: item.name,
                description: item.description,
            };
        });

        return {items};
    }
}

interface ItemViewOptions {
    targetEntityType: string | null;
    mode: 'edit' | 'detail';
    model: Model<ItemSchema>;
    onRemove: () => void;
}

class ItemView extends View<{
    model: Model<ItemSchema>;
    options: ItemViewOptions;
}> {

    // language=Handlebars
    protected templateContent = `
        <div class="row">
            <div class=" {{columnClassName}} " data-role="label">{{label}}</div>
            <div class=" {{columnClassName}} " data-role="description">{{{descriptionField}}}</div>
            {{#if isEditMode}}
                <div class="col-md-2" style="text-align: center;">
                    <a
                        role="button"
                        data-action="removeRow"
                        class="btn btn-link btn-icon pull-right"
                        title="{{translate 'Remove'}}"
                    ><span class="fas fa-times"></span></a>
                </div>
            {{/if}}
        </div>
    `

    private readonly mode: 'edit' | 'detail'

    private readonly targetEntityType: string | null

    private descriptionView: TextFieldView

    protected data(): Record<string, any> {
        const label = this.translate(this.model.attributes.name!, 'fields', this.targetEntityType);

        return {
            label,
            isEditMode: this.mode === 'edit',
            columnClassName: this.mode === 'edit' ? 'col-md-5' : 'col-md-6',
        };
    }

    constructor(options: ItemViewOptions) {
        super(options);

        this.targetEntityType = options.targetEntityType;
        this.mode = options.mode;
    }

    protected setup() {
        this.addActionHandler('removeRow', () => this.options.onRemove());

        this.descriptionView = new TextFieldView({
            name: 'description',
            mode: this.mode,
            model: this.model,
            params: {
                rowsMin: 1,
            },
        });

        this.assignView('descriptionField', this.descriptionView);
    }

    validate(): boolean {
        return this.descriptionView.validate();
    }
}
