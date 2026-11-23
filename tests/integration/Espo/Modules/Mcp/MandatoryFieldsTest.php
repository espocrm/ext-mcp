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

use Espo\Core\Acl\Table;
use Espo\Core\Api\RequestWrapper;
use Espo\Core\Authentication\Logins\ApiKey;
use Espo\Core\Name\Field;
use Espo\Core\Utils\Json;
use Espo\Entities\Role;
use Espo\Entities\User;
use Espo\Modules\Crm\Entities\Account;
use Espo\Modules\Crm\Entities\Lead;
use Espo\Modules\Mcp\Entities\Endpoint;
use Espo\Modules\Mcp\Entities\Feature;
use Espo\Modules\Mcp\Tools\Feature\Features\Create\CreateData;
use Espo\Modules\Mcp\Tools\Feature\Features\Find\FindData;
use Espo\Modules\Mcp\Tools\Feature\Features\Read\ReadData;
use Espo\Modules\Mcp\Tools\Feature\Features\Update\UpdateData;
use Espo\Modules\Mcp\Tools\Mcp\Api\PostEntry;
use Espo\Modules\Mcp\Tools\Mcp\Method;
use Espo\Modules\Mcp\Tools\Mcp\Scope;
use RuntimeException;
use stdClass;
use tests\integration\Core\BaseTestCase;

/**
 * Mandatory fields defined in `mcpDefs.{entityType}.fields.{field}`.
 *
 * Configured Lead features:
 * - Find: select `name`, `status`; filter `status`.
 * - Read: select `name`, `status`.
 * - Create, Update: write `lastName`, `status`.
 */
class MandatoryFieldsTest extends BaseTestCase
{
    private const string TEST_API_KEY = 'test-key';
    private const string SLUG = 'test';

    private PostEntry $apiAction;

    /**
     * `mandatory: true` adds a field to all actions.
     */
    public function testMandatoryForAllActions(): void
    {
        $this->prepare();

        $this->setScopeFields(Lead::ENTITY_TYPE, [
            'website' => ['mandatory' => true],
        ]);

        $tools = $this->listTools();

        $find = $tools['Find_Lead'];

        // Mandatory fields go first.
        $this->assertEquals(['website', 'name', 'status'], $this->getSelectOptions($find));
        $this->assertEquals(['website', 'status'], $this->getWhereAttributes($find));
        $this->assertObjectHasProperty('website', $find->outputSchema->properties->records->items->properties);

        $read = $tools['Read_Lead'];

        $this->assertEquals(['website', 'name', 'status'], $this->getSelectOptions($read));
        $this->assertObjectHasProperty('website', $read->outputSchema->properties->record->properties);

        $this->assertObjectHasProperty('website', $this->getWriteProperties($tools['Create_Lead']));
        $this->assertObjectHasProperty('website', $this->getWriteProperties($tools['Update_Lead']));
    }

    /**
     * `{action}: 'mandatory'` adds a field to that action only.
     */
    public function testMandatoryPerAction(): void
    {
        $this->prepare();

        $this->setScopeFields(Lead::ENTITY_TYPE, [
            'website' => ['select' => 'mandatory'],
            'industry' => ['create' => 'mandatory'],
        ]);

        $tools = $this->listTools();

        $find = $tools['Find_Lead'];

        $this->assertEquals(['website', 'name', 'status'], $this->getSelectOptions($find));
        $this->assertEquals(['status'], $this->getWhereAttributes($find));

        // The 'select' action is for Find only, Read uses 'read'.
        $this->assertEquals(['name', 'status'], $this->getSelectOptions($tools['Read_Lead']));

        $createProperties = $this->getWriteProperties($tools['Create_Lead']);

        $this->assertObjectHasProperty('industry', $createProperties);
        $this->assertObjectNotHasProperty('website', $createProperties);

        $this->assertObjectNotHasProperty('industry', $this->getWriteProperties($tools['Update_Lead']));
    }

    /**
     * A mandatory filter field adds the `where` parameter even if no filter fields are configured.
     */
    public function testMandatoryFilterWithNoConfiguredFilterFields(): void
    {
        $this->prepare(withAccountFind: true);

        $this->setScopeFields(Account::ENTITY_TYPE, [
            'website' => ['filter' => 'mandatory'],
        ]);

        $find = $this->listTools()['Find_Account'];

        $this->assertEquals(['website'], $this->getWhereAttributes($find));
        $this->assertEquals([Field::NAME], $this->getSelectOptions($find));
    }

    /**
     * No `where` parameter if no filter fields are configured and none are mandatory.
     */
    public function testNoMandatoryFilterWithNoConfiguredFilterFields(): void
    {
        $this->prepare(withAccountFind: true);

        $find = $this->listTools()['Find_Account'];

        $this->assertNull($this->getWhereAttributes($find));
    }

    /**
     * `{action}: false` excludes a field marked with `mandatory: true` from that action.
     */
    public function testActionFalseOverridesMandatory(): void
    {
        $this->prepare();

        $this->setScopeFields(Lead::ENTITY_TYPE, [
            'website' => [
                'mandatory' => true,
                'filter' => false,
                'update' => false,
            ],
        ]);

        $tools = $this->listTools();

        $find = $tools['Find_Lead'];

        $this->assertEquals(['website', 'name', 'status'], $this->getSelectOptions($find));
        $this->assertEquals(['status'], $this->getWhereAttributes($find));

        $this->assertObjectHasProperty('website', $this->getWriteProperties($tools['Create_Lead']));
        $this->assertObjectNotHasProperty('website', $this->getWriteProperties($tools['Update_Lead']));
    }

    /**
     * `{action}: false` removes a field configured in a feature from that action.
     */
    public function testActionFalseRemovesConfiguredField(): void
    {
        $this->prepare();

        $this->setScopeFields(Lead::ENTITY_TYPE, [
            'status' => [
                'select' => false,
                'create' => false,
            ],
        ]);

        $tools = $this->listTools();

        $find = $tools['Find_Lead'];

        $this->assertEquals(['name'], $this->getSelectOptions($find));
        $this->assertObjectNotHasProperty('status', $find->outputSchema->properties->records->items->properties);
        // Filter is not affected.
        $this->assertEquals(['status'], $this->getWhereAttributes($find));

        $this->assertEquals(['name', 'status'], $this->getSelectOptions($tools['Read_Lead']));

        $this->assertObjectNotHasProperty('status', $this->getWriteProperties($tools['Create_Lead']));
        $this->assertObjectHasProperty('status', $this->getWriteProperties($tools['Update_Lead']));
    }

    /**
     * A field that is both configured and mandatory is listed once, first.
     */
    public function testConfiguredAndMandatoryNotDuplicated(): void
    {
        $this->prepare();

        $this->setScopeFields(Lead::ENTITY_TYPE, [
            'status' => ['mandatory' => true],
        ]);

        $tools = $this->listTools();

        $find = $tools['Find_Lead'];

        $this->assertEquals(['status', 'name'], $this->getSelectOptions($find));
        $this->assertEquals(['status'], $this->getWhereAttributes($find));

        $this->assertEquals(['status', 'name'], $this->getSelectOptions($tools['Read_Lead']));
    }

    /**
     * Mandatory fields that do not exist or are disabled are skipped and do not break tool listing.
     */
    public function testNotExistingAndDisabledMandatoryFieldsSkipped(): void
    {
        $this->prepare();

        $this->getMetadata()->set('entityDefs', Lead::ENTITY_TYPE, [
            'fields' => [
                'industry' => ['disabled' => true],
            ],
        ]);

        $this->setScopeFields(Lead::ENTITY_TYPE, [
            'notExistingField' => ['mandatory' => true],
            'industry' => ['mandatory' => true],
        ]);

        $tools = $this->listTools();

        $find = $tools['Find_Lead'];

        $this->assertEquals(['name', 'status'], $this->getSelectOptions($find));
        $this->assertEquals(['status'], $this->getWhereAttributes($find));

        $this->assertEquals(['name', 'status'], $this->getSelectOptions($tools['Read_Lead']));

        $this->assertObjectNotHasProperty('industry', $this->getWriteProperties($tools['Create_Lead']));
        $this->assertObjectNotHasProperty('industry', $this->getWriteProperties($tools['Update_Lead']));
    }

    /**
     * A mandatory field can be selected and filtered by in a Find tool call.
     */
    public function testFindCallWithMandatoryField(): void
    {
        $em = $this->getEntityManager();

        $em->createEntity(Lead::ENTITY_TYPE, [
            'lastName' => 'Lead 1',
            'website' => 'https://one.test',
        ]);

        $em->createEntity(Lead::ENTITY_TYPE, [
            'lastName' => 'Lead 2',
            'website' => 'https://two.test',
        ]);

        $this->prepare();

        $this->setScopeFields(Lead::ENTITY_TYPE, [
            'website' => ['mandatory' => true],
        ]);

        $body = $this->call(Method::TOOLS_CALL, (object) [
            'name' => 'Find_Lead',
            'arguments' => (object) [
                'selectFields' => [Field::NAME, 'website'],
                'where' => [
                    (object) [
                        'attribute' => 'website',
                        'type' => 'equals',
                        'value' => 'https://two.test',
                    ],
                ],
            ],
        ]);

        $this->assertObjectHasProperty('result', $body, "Error: " . Json::encode($body->error ?? null));
        $this->assertFalse($body->result->isError ?? false, Json::encode($body->result));

        $records = $body->result->structuredContent->records;

        $this->assertCount(1, $records);
        $this->assertEquals('Lead 2', $records[0]->name);
        $this->assertEquals('https://two.test', $records[0]->website);
    }

    private function prepare(bool $withAccountFind = false): void
    {
        $this->setConfigParams([
            'siteUrl' => 'http://localhost',
        ]);

        $apiUser = $this->createApiUser();
        $endpoint = $this->createEndpoint($apiUser);

        $this->createFeatures($endpoint, $withAccountFind);

        // Authentication resets metadata. Metadata is to be set after.
        $this->authenticateApi();
    }

    /**
     * @param array<string, array<string, mixed>> $fields
     */
    private function setScopeFields(string $entityType, array $fields): void
    {
        $this->getMetadata()->set('mcpDefs', $entityType, [
            'fields' => $fields,
        ]);
    }

    /**
     * @return array<string, stdClass>
     */
    private function listTools(): array
    {
        $body = $this->call(Method::TOOLS_LIST);

        $this->assertObjectHasProperty('result', $body, "Error: " . Json::encode($body->error ?? null));

        $output = [];

        foreach ($body->result->tools as $tool) {
            $output[$tool->name] = $tool;
        }

        return $output;
    }

    /**
     * @return string[]
     */
    private function getSelectOptions(stdClass $tool): array
    {
        $schema = $tool->inputSchema->properties->selectFields ?? null;

        $this->assertNotNull($schema, "No 'selectFields' in '$tool->name'.");

        return array_map(fn ($it) => $it->const, $schema->items->anyOf);
    }

    /**
     * @return ?string[] Null if there's no `where` parameter.
     */
    private function getWhereAttributes(stdClass $tool): ?array
    {
        $schema = $tool->inputSchema->properties->where ?? null;

        if (!$schema) {
            return null;
        }

        $attributes = array_map(fn ($it) => $it->properties->attribute->const, $schema->items->anyOf);

        return array_values(array_unique($attributes));
    }

    private function getWriteProperties(stdClass $tool): stdClass
    {
        return $tool->inputSchema->properties->record->properties;
    }

    /**
     * @noinspection PhpUnhandledExceptionInspection
     */
    private function call(string $method, ?stdClass $params = null): stdClass
    {
        $response = $this->apiAction->process(
            $this->createEntryRequest(
                method: $method,
                id: '1',
                params: $params,
            ),
        );

        $body = Json::decode($response->getBody());

        if (!$body instanceof stdClass) {
            throw new RuntimeException("Bad response body: " . $response->getBody());
        }

        return $body;
    }

    private function authenticateApi(): void
    {
        $request = $this->createEntryRequest(
            method: Method::SERVER_DISCOVER,
            jsonrpc: '1.0',
        );

        $this->authenticate(
            method: ApiKey::NAME,
            request: $request,
        );

        $this->apiAction = $this->getInjectableFactory()->create(PostEntry::class);
    }

    private function createEntryRequest(
        string $method,
        ?string $jsonrpc = '2.0',
        ?string $id = null,
        ?stdClass $params = null,
    ): RequestWrapper {

        $protocolVersion = '2026-07-28';

        $body = [
            'method' => $method,
        ];

        if ($id !== null) {
            $body['id'] = $id;
        }

        if ($jsonrpc !== null) {
            $body['jsonrpc'] = $jsonrpc;
        }

        if ($params !== null) {
            $body['params'] = $params;
        }

        $body['_meta'] = [
            'io.modelcontextprotocol/protocolVersion' => $protocolVersion,
        ];

        return $this->createRequest(
            method: 'POST',
            headers: [
                'Content-Type' => 'application/json',
                'X-Api-Key' => self::TEST_API_KEY,
                'MCP-Protocol-Version' => $protocolVersion,
            ],
            body: Json::encode($body),
            routeParams: [
                'slug' => self::SLUG,
            ],
        );
    }

    private function createFeatures(Endpoint $endpoint, bool $withAccountFind): void
    {
        $em = $this->getEntityManager();

        $em->saveEntity(
            $em->getRDBRepositoryByClass(Feature::class)->getNew()
                ->setType(FindData::TYPE)
                ->setData(
                    new FindData(
                        entityType: Lead::ENTITY_TYPE,
                        textFilter: false,
                        selectFields: [
                            new FindData\Field(Field::NAME),
                            new FindData\Field('status'),
                        ],
                        primaryFilters: [],
                        boolFilters: [],
                        filterFields: [
                            new FindData\Field('status'),
                        ],
                    )
                )
                ->setEndpoint($endpoint)
        );

        $em->saveEntity(
            $em->getRDBRepositoryByClass(Feature::class)->getNew()
                ->setType(ReadData::TYPE)
                ->setData(
                    new ReadData(
                        entityType: Lead::ENTITY_TYPE,
                        selectFields: [
                            new FindData\Field(Field::NAME),
                            new FindData\Field('status'),
                        ],
                    )
                )
                ->setEndpoint($endpoint)
        );

        $em->saveEntity(
            $em->getRDBRepositoryByClass(Feature::class)->getNew()
                ->setType(CreateData::TYPE)
                ->setData(
                    new CreateData(
                        entityType: Lead::ENTITY_TYPE,
                        writeFields: [
                            new FindData\Field('lastName'),
                            new FindData\Field('status'),
                        ],
                    )
                )
                ->setEndpoint($endpoint)
        );

        $em->saveEntity(
            $em->getRDBRepositoryByClass(Feature::class)->getNew()
                ->setType(UpdateData::TYPE)
                ->setData(
                    new UpdateData(
                        entityType: Lead::ENTITY_TYPE,
                        writeFields: [
                            new FindData\Field('lastName'),
                            new FindData\Field('status'),
                        ],
                    )
                )
                ->setEndpoint($endpoint)
        );

        if (!$withAccountFind) {
            return;
        }

        $em->saveEntity(
            $em->getRDBRepositoryByClass(Feature::class)->getNew()
                ->setType(FindData::TYPE)
                ->setData(
                    new FindData(
                        entityType: Account::ENTITY_TYPE,
                        textFilter: false,
                        selectFields: [
                            new FindData\Field(Field::NAME),
                        ],
                        primaryFilters: [],
                        boolFilters: [],
                        filterFields: [],
                    )
                )
                ->setEndpoint($endpoint)
        );
    }

    private function createEndpoint(User $apiUser): Endpoint
    {
        $em = $this->getEntityManager();

        $endpoint = $em->getRDBRepositoryByClass(Endpoint::class)->getNew()
            ->setName('Test')
            ->setSlug(self::SLUG)
            ->setPublicDescription('Test.');
        $em->saveEntity($endpoint);

        $em->getRelation($endpoint, Endpoint::LINK_USERS)->relate($apiUser);

        return $endpoint;
    }

    private function createApiUser(): User
    {
        return $this->createUser([
            User::FIELD_USER_NAME => 'api',
            User::FIELD_TYPE => User::TYPE_API,
            User::FIELD_AUTH_METHOD => User::AUTH_METHOD_API_KEY,
            User::FIELD_API_KEY => self::TEST_API_KEY,
        ], [
            Role::FIELD_DATA => [
                Scope::MCP => true,
                Lead::ENTITY_TYPE => [
                    Table::ACTION_CREATE => Table::LEVEL_YES,
                    Table::ACTION_READ => Table::LEVEL_ALL,
                    Table::ACTION_EDIT => Table::LEVEL_ALL,
                ],
                Account::ENTITY_TYPE => [
                    Table::ACTION_READ => Table::LEVEL_ALL,
                ],
            ],
        ]);
    }
}
