/**LICENSE**/

import {inject} from 'di';
import FieldManager from 'field-manager';
import Metadata from 'metadata';

interface ItemDefs {
    filter?: boolean;
    select?: boolean;
    read?: boolean;
    create?: boolean;
    update?: boolean;
}

export default class SchemaUtil {

    @inject(Metadata)
    private metadata: Metadata

    @inject(FieldManager)
    private fieldManager: FieldManager

    getFeatureFields(options: {
        entityType: string,
        type: 'filter' | 'select' | 'read' | 'create' | 'update',
    }): string[] {

        const entityType = options.entityType;
        const type = options.type;

        const fieldTypes = this.metadata.get(`app.mcpSchema.fieldTypes`, {}) as Record<string, ItemDefs>;

        const types = Object.keys(fieldTypes).filter(it => fieldTypes[it][type]);

        return this.fieldManager.getEntityTypeFieldList(entityType, {
            onlyAvailable: true,
            typeList: types,
        });
    }
}
