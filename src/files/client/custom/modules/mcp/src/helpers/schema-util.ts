/************************************************************************
* This file is part of MCP extension for EspoCRM.
*
* MCP extension for EspoCRM.
* Copyright (C) 2026 EspoCRM, Inc.
* Website: https://www.espocrm.com
*
* This program is free software: you can redistribute it and/or modify
* it under the terms of the GNU Affero General Public License as published by
* the Free Software Foundation, either version 3 of the License, or
* (at your option) any later version.
*
* This program is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
* GNU Affero General Public License for more details.
*
* You should have received a copy of the GNU Affero General Public License
* along with this program. If not, see <https://www.gnu.org/licenses/>.
*
* The interactive user interfaces in modified source and object code versions
* of this program must display Appropriate Legal Notices, as required under
* Section 5 of the GNU Affero General Public License version 3.
*
* In accordance with Section 7(b) of the GNU Affero General Public License version 3,
* these Appropriate Legal Notices must retain the display of the "EspoCRM" word.
************************************************************************/

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

type FieldActionLevel = boolean | 'mandatory';

interface FieldMcpDefs {
    mandatory?: boolean;
    filter?: FieldActionLevel;
    select?: FieldActionLevel;
    read?: FieldActionLevel;
    create?: FieldActionLevel;
    update?: FieldActionLevel;
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

        const fields = this.fieldManager.getEntityTypeFieldList(entityType, {
            onlyAvailable: true,
            typeList: types,
        });

        return fields.filter(it => {
            const level = this.metadata.get(`mcpDefs.${entityType}.fields.${it}.${type}`);

            if (
                this.metadata.get(`mcpDefs.${entityType}.fields.${it}.mandatory`) ||
                level === 'mandatory' ||
                level === false
            ) {
                return false;
            }

            return true;
        });
    }

    getMandatoryFeatureFields(options: {
        entityType: string,
        type: 'filter' | 'select' | 'read' | 'create' | 'update',
    }): string[] {
        const {entityType, type} = options;

        const fieldDefs = this.metadata.get(`mcpDefs.${entityType}.fields`, {}) as Record<string, FieldMcpDefs>;

        return Object.entries(fieldDefs)
            .filter(([field, defs]) => {
                if (
                    !this.metadata.get(`entityDefs.${entityType}.fields.${field}`) ||
                    this.metadata.get(`entityDefs.${entityType}.fields.${field}.disabled`)
                ) {
                    return false;
                }

                const level = defs[type];

                return (defs.mandatory && level !== false) || level === 'mandatory';
            })
            .map(([field]) => field);
    }

    getFeatureLinks(options: {entityType: string}): string[] {
        const entityType = options.entityType;

        const linkTypes = [
            'hasMany',
            'hasChildren',
        ];

        const linksDefs = this.metadata.get(`entityDefs.${entityType}.links`, {}) as
            Record<string, Record<string, any>>;

        return Object.keys(linksDefs).filter(link => {
            const defs = linksDefs[link] ?? {};

            if (
                !linkTypes.includes(defs.type) ||
                defs.disabled ||
                defs.utility ||
                defs.readOnly
            ) {
                return false;
            }

            const aclDefs = this.metadata.get(`entityAcl.${entityType}.links.${link}`, {}) as Record<string, any>;

            if (
                aclDefs.forbidden ||
                // Allowed but not listed.
                aclDefs.internal
            ) {
                return false;
            }

            const foreignEntityType = defs.entity as string | undefined;

            if (
                !foreignEntityType ||
                !this.metadata.get(`scopes.${foreignEntityType}.entity`) ||
                this.metadata.get(`scopes.${foreignEntityType}.disabled`)
            ) {
                return false;
            }

            return true;
        });
    }
}
