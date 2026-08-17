/**LICENSE**/

import BaseFieldView from 'views/fields/base';
import Collection from 'collection';
import Model from 'model';
import View from 'view';
import TextFieldView from 'views/fields/text';
import ArrayFieldAdd from 'views/modals/array-field-add';

interface ItemSchema {
    name: string;
    description?: string | null;
}

export default class FeatureRecordFieldsFieldView extends BaseFieldView<{
    model: Model<Record<string, any> & {
        entityType: string | null,
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

    protected markRequired: boolean = false

    protected skipReadOnly: boolean = true

    protected skipReadOnlyAfterCreate: boolean = true

    private itemCollection: Collection<Model<ItemSchema>> | null = null

    private itemViews: ItemView[]

    protected hasDescription: boolean = true

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
        this.addActionHandler('add', () => this.add());

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
                targetEntityType: this.model.attributes.entityType ?? null,
                hasDescription: this.hasDescription,
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
            const output: ItemSchema = {
                name: item.attributes.name!,
            };

            if (this.hasDescription) {
                output.description = item.attributes.description ?? null;
            }

            return output;
        });
    }

    private async add() {
        const items = this.getItemsFromModel() ?? [];

        const currentFields = this.getItemsFromModel()?.map(it => it.name) ?? [];

        const targetEntityType = this.model.attributes.entityType;

        if (!targetEntityType) {
            throw new Error();
        }

        let fields = (this.recordHelper?.getFieldOptionList(this.name) ?? [])
            .filter(it => !currentFields.includes(it));

        if (this.skipReadOnly) {
            fields = fields.filter(field => {
                return !this.getMetadata().get(`entityDefs.${targetEntityType}.fields.${field}.readOnly`);
            });
        }

        if (this.skipReadOnlyAfterCreate) {
            fields = fields.filter(field => {
                return !this.getMetadata().get(`entityDefs.${targetEntityType}.fields.${field}.readOnlyAfterCreate`);
            });
        }

        const translatedOptions = this.getFieldTranslations(targetEntityType, fields);

        fields = fields.sort((a, b) => {
            if (this.markRequired) {
                const aRequired = this.isFieldRequired(targetEntityType, a);
                const bRequired = this.isFieldRequired(targetEntityType, b);

                if (aRequired !== bRequired) {
                    return aRequired ? -1 : 1;
                }
            }

            const labelA = translatedOptions[a] ?? a;
            const labelB = translatedOptions[b] ?? b;

            return labelA.localeCompare(labelB);
        });

        const view = new ArrayFieldAdd({
            options: fields,
            translatedOptions: translatedOptions,
        });

        await this.assignView('dialog', view);
        await view.render();

        this.listenTo(view, 'add-mass', (names: string[]) => {
            add(names);

            view.close();
        });

        this.listenTo(view, 'add', (name: string) => {
            add([name]);

            view.close();
        });

        const add = async (names: string[]) => {
            names.forEach(name => {
                const item: ItemSchema = {name};

                if (this.hasDescription) {
                    item.description = null;
                }

                items.push(item);
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

        this.model.setMultiple({
            [this.name]: items,
        }, {ui: true});

        await this.prepare();
        await this.reRender();
    }

    private getFieldTranslations(entityType: string, fields: string[]): Record<string, string> {
        const translations: Record<string, string> = {};

        fields.forEach(field => {
            let label = this.getLanguage().translate(field, 'fields', entityType);

            if (this.markRequired && this.isFieldRequired(entityType, field)) {
                label += ' *';
            }

            translations[field] = label
        });

        return translations;
    }

    private isFieldRequired(entityType: string, field: string): boolean {
        return !!this.getMetadata().get(`entityDefs.${entityType}.fields.${field}.required`);
    }

    fetch(): Record<string, any> {
        const items = this.getItemsFromCollection().map(item => ({...item}));

        return {
            [this.name]: items,
        };
    }
}

interface ItemViewOptions {
    targetEntityType: string | null;
    mode: 'edit' | 'detail';
    model: Model<ItemSchema>;
    onRemove: () => void;
    hasDescription: boolean,
}

class ItemView extends View<{
    model: Model<ItemSchema>;
    options: ItemViewOptions;
}> {

    // language=Handlebars
    protected templateContent = `
        <div class="row">
            <div
                class=" {{#if isEditMode}} detail-field-container {{/if}} col-md-6"
                data-role="label"
            >{{label}}</div>
            <div class=" {{columnClassName}} " data-role="description">{{{descriptionField}}}</div>
            {{#if isEditMode}}
                <div class="col-md-1" style="text-align: center;">
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

    private descriptionView: TextFieldView | null = null;

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

        if (this.options.hasDescription) {
            this.descriptionView = new TextFieldView({
                name: 'description',
                mode: this.mode === 'edit' ? 'edit' : 'list',
                model: this.model,
                params: {
                    rowsMin: 1,
                },
                readOnly: this.mode === 'detail',
            });

            this.assignView('descriptionField', this.descriptionView);
        }
    }

    validate(): boolean {
        let notValid = false;

        if (this.descriptionView && this.descriptionView.validate()) {
            notValid = true;
        }

        return notValid;
    }
}
