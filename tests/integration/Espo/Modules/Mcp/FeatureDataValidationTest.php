<?php
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

namespace integration\Espo\Modules\Mcp;

use Espo\Entities\Role;
use Espo\Entities\Team;
use Espo\Entities\User;
use Espo\Modules\Crm\Entities\Account;
use Espo\Modules\Crm\Entities\Lead;
use Espo\Modules\Mcp\Tools\Feature\Data;
use Espo\Modules\Mcp\Tools\Feature\DataValidator\Failure;
use Espo\Modules\Mcp\Tools\Feature\DataValidatorFactory;
use Espo\Modules\Mcp\Tools\Feature\Features\Create\CreateData;
use Espo\Modules\Mcp\Tools\Feature\Features\Delete\DeleteData;
use Espo\Modules\Mcp\Tools\Feature\Features\Find\FindData;
use Espo\Modules\Mcp\Tools\Feature\Features\Link\LinkData;
use Espo\Modules\Mcp\Tools\Feature\Features\Read\ReadData;
use Espo\Modules\Mcp\Tools\Feature\Features\Update\UpdateData;
use RuntimeException;
use tests\integration\Core\BaseTestCase;

class FeatureDataValidationTest extends BaseTestCase
{
    private const array TYPES = [
        FindData::TYPE,
        ReadData::TYPE,
        CreateData::TYPE,
        UpdateData::TYPE,
        DeleteData::TYPE,
        LinkData::TYPE,
    ];

    public function testObjectEntityTypeAllowed(): void
    {
        foreach (self::TYPES as $type) {
            $this->assertAllowed($type, Lead::ENTITY_TYPE);
        }
    }

    public function testNonObjectEntityTypeNotAllowed(): void
    {
        foreach (self::TYPES as $type) {
            $this->assertNotAllowed($type, Role::ENTITY_TYPE);
        }
    }

    public function testNonExistingEntityTypeNotAllowed(): void
    {
        foreach (self::TYPES as $type) {
            $this->assertNotAllowed($type, 'NonExistingEntityType');
        }
    }

    public function testUserOverride(): void
    {
        $this->assertAllowed(FindData::TYPE, User::ENTITY_TYPE);
        $this->assertAllowed(ReadData::TYPE, User::ENTITY_TYPE);

        $this->assertNotAllowed(CreateData::TYPE, User::ENTITY_TYPE);
        $this->assertNotAllowed(UpdateData::TYPE, User::ENTITY_TYPE);
        $this->assertNotAllowed(DeleteData::TYPE, User::ENTITY_TYPE);
        $this->assertNotAllowed(LinkData::TYPE, User::ENTITY_TYPE);
    }

    public function testTeamOverride(): void
    {
        $this->assertAllowed(FindData::TYPE, Team::ENTITY_TYPE);
        $this->assertAllowed(ReadData::TYPE, Team::ENTITY_TYPE);

        $this->assertNotAllowed(CreateData::TYPE, Team::ENTITY_TYPE);
        $this->assertNotAllowed(UpdateData::TYPE, Team::ENTITY_TYPE);
        $this->assertNotAllowed(DeleteData::TYPE, Team::ENTITY_TYPE);
        $this->assertNotAllowed(LinkData::TYPE, Team::ENTITY_TYPE);
    }

    public function testCustomOverrideEnablesNonObject(): void
    {
        $this->getMetadata()->set('mcpDefs', Role::ENTITY_TYPE, [
            'features' => [
                FindData::TYPE => true,
                ReadData::TYPE => true,
            ],
        ]);

        $this->assertAllowed(FindData::TYPE, Role::ENTITY_TYPE);
        $this->assertAllowed(ReadData::TYPE, Role::ENTITY_TYPE);

        // Not overridden, falls back to the object check.
        $this->assertNotAllowed(CreateData::TYPE, Role::ENTITY_TYPE);
        $this->assertNotAllowed(UpdateData::TYPE, Role::ENTITY_TYPE);
        $this->assertNotAllowed(DeleteData::TYPE, Role::ENTITY_TYPE);

        // Shipped overrides are kept.
        $this->assertAllowed(FindData::TYPE, User::ENTITY_TYPE);
        $this->assertNotAllowed(CreateData::TYPE, User::ENTITY_TYPE);
    }

    public function testCustomOverrideDisablesObject(): void
    {
        $this->getMetadata()->set('mcpDefs', Lead::ENTITY_TYPE, [
            'features' => [
                DeleteData::TYPE => false,
            ],
        ]);

        $this->assertNotAllowed(DeleteData::TYPE, Lead::ENTITY_TYPE);

        $this->assertAllowed(FindData::TYPE, Lead::ENTITY_TYPE);
        $this->assertAllowed(ReadData::TYPE, Lead::ENTITY_TYPE);
        $this->assertAllowed(CreateData::TYPE, Lead::ENTITY_TYPE);
        $this->assertAllowed(UpdateData::TYPE, Lead::ENTITY_TYPE);
    }

    public function testDisabledScopeNotAllowed(): void
    {
        $this->getMetadata()->set('scopes', Lead::ENTITY_TYPE, ['disabled' => true]);
        $this->getMetadata()->set('scopes', Team::ENTITY_TYPE, ['disabled' => true]);

        foreach (self::TYPES as $type) {
            $this->assertNotAllowed($type, Lead::ENTITY_TYPE);
        }

        // Override does not bypass the disabled check.
        $this->assertNotAllowed(FindData::TYPE, Team::ENTITY_TYPE);
        $this->assertNotAllowed(ReadData::TYPE, Team::ENTITY_TYPE);
    }

    public function testNonEntityScopeNotAllowedEvenWithOverride(): void
    {
        $this->getMetadata()->set('scopes', 'TestMcpNonEntity', [
            'entity' => false,
            'object' => true,
        ]);

        $this->getMetadata()->set('mcpDefs', 'TestMcpNonEntity', [
            'features' => [
                FindData::TYPE => true,
            ],
        ]);

        $this->assertNotAllowed(FindData::TYPE, 'TestMcpNonEntity');
        $this->assertNotAllowed(ReadData::TYPE, 'TestMcpNonEntity');
    }

    public function testLinkAllowed(): void
    {
        $this->assertEmpty($this->getLinkFailures(['contacts', 'opportunities', 'meetings']));
    }

    public function testLinkEmpty(): void
    {
        $this->assertNotEmpty($this->getLinkFailures([]));
    }

    public function testLinkDuplicate(): void
    {
        $this->assertNotEmpty($this->getLinkFailures(['contacts', 'contacts']));
    }

    public function testLinkNonExisting(): void
    {
        $this->assertNotEmpty($this->getLinkFailures(['nonExistingLink']));
    }

    public function testLinkBelongsTo(): void
    {
        $this->assertNotEmpty($this->getLinkFailures(['campaign']));
    }

    public function testLinkReadOnly(): void
    {
        $this->getMetadata()->set('entityDefs', Account::ENTITY_TYPE, [
            'links' => [
                'documents' => [
                    'readOnly' => true,
                ],
            ],
        ]);

        $this->assertNotEmpty($this->getLinkFailures(['documents']));
    }

    public function testLinkAclForbidden(): void
    {
        $this->getMetadata()->set('entityAcl', Account::ENTITY_TYPE, [
            'links' => [
                'cases' => [
                    'forbidden' => true,
                ],
            ],
        ]);

        $this->getMetadata()->save();

        $this->reCreateApplication(reuse: true);

        $this->assertNotEmpty($this->getLinkFailures(['cases']), "Expected 'forbidden' link to be not allowed.");

        $this->getMetadata()->set('entityAcl', Account::ENTITY_TYPE, [
            'links' => [
                'cases' => [
                    'forbidden' => false,
                ],
            ],
        ]);

        $this->reCreateApplication(reuse: true);

        $this->getMetadata()->save();

        $this->assertEmpty($this->getLinkFailures(['cases']));
    }

    /**
     * @param string[] $links
     * @return Failure[]
     * @noinspection PhpUnhandledExceptionInspection
     */
    private function getLinkFailures(array $links): array
    {
        $validator = $this->getInjectableFactory()->create(DataValidatorFactory::class)
            ->create(LinkData::TYPE);

        $data = new LinkData(
            entityType: Account::ENTITY_TYPE,
            links: array_map(fn ($it) => new LinkData\Link($it), $links),
        );

        return $validator->validate($data);
    }

    private function assertAllowed(string $type, string $entityType): void
    {
        $this->assertEmpty(
            $this->getEntityTypeFailures($type, $entityType),
            "Expected '$entityType' to be allowed for '$type'."
        );
    }

    private function assertNotAllowed(string $type, string $entityType): void
    {
        $this->assertNotEmpty(
            $this->getEntityTypeFailures($type, $entityType),
            "Expected '$entityType' to be not allowed for '$type'."
        );
    }

    /**
     * @return Failure[]
     * @noinspection PhpUnhandledExceptionInspection
     */
    private function getEntityTypeFailures(string $type, string $entityType): array
    {
        $validator = $this->getInjectableFactory()->create(DataValidatorFactory::class)
            ->create($type);

        $failures = $validator->validate($this->createData($type, $entityType));

        $failures = array_filter($failures, fn (Failure $it) => $it->field === 'entityType');

        return array_values($failures);
    }

    private function createData(string $type, string $entityType): Data
    {
        return match ($type) {
            FindData::TYPE => new FindData(
                entityType: $entityType,
                textFilter: false,
                selectFields: [],
                primaryFilters: [],
                boolFilters: [],
                filterFields: [],
            ),
            ReadData::TYPE => new ReadData(entityType: $entityType, selectFields: []),
            CreateData::TYPE => new CreateData(entityType: $entityType, writeFields: []),
            UpdateData::TYPE => new UpdateData(entityType: $entityType, writeFields: []),
            DeleteData::TYPE => new DeleteData(entityType: $entityType),
            LinkData::TYPE => new LinkData(entityType: $entityType, links: []),
            default => throw new RuntimeException("Unknown type '$type'."),
        };
    }
}
