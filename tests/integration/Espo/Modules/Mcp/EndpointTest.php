<?php
/**LICENSE**/

namespace integration\Espo\Modules\Mcp;

use Espo\Core\Acl\Table;
use Espo\Core\Api\RequestWrapper;
use Espo\Core\Authentication\Logins\ApiKey;
use Espo\Core\Binding\BindingContainerBuilder;
use Espo\Core\Field\Date;
use Espo\Core\Field\DateTimeOptional;
use Espo\Core\Field\EmailAddress;
use Espo\Core\Field\EmailAddressGroup;
use Espo\Core\Field\LinkMultiple;
use Espo\Core\Name\Field;
use Espo\Core\Select\Where\Item\Type;
use Espo\Core\Utils\Json;
use Espo\Entities\Role;
use Espo\Entities\Team;
use Espo\Entities\User;
use Espo\Modules\Crm\Entities\Account;
use Espo\Modules\Crm\Entities\Call;
use Espo\Modules\Crm\Entities\Lead;
use Espo\Modules\Crm\Entities\Opportunity;
use Espo\Modules\Crm\Entities\Task;
use Espo\Modules\Mcp\Entities\Endpoint;
use Espo\Modules\Mcp\Entities\Feature;
use Espo\Modules\Mcp\Tools\Feature\Find\FindData;
use Espo\Modules\Mcp\Tools\Mcp\Api\PostEntry;
use Espo\Modules\Mcp\Tools\Mcp\Exceptions\InvalidParamsError;
use Espo\Modules\Mcp\Tools\Mcp\JsonSchemaValidator\Validator;
use Espo\Modules\Mcp\Tools\Mcp\Method;
use Espo\Modules\Mcp\Tools\Mcp\Scope;
use Espo\Modules\Mcp\Tools\Mcp\Tool\ToolEnvelope;
use Espo\Modules\Mcp\Tools\Mcp\Tool\ToolProvider;
use RuntimeException;
use tests\integration\Core\BaseTestCase;

class EndpointTest extends BaseTestCase
{
    private const string TEST_API_KEY = 'test-key';

    /**
     * @noinspection PhpUnhandledExceptionInspection
     */
    public function testEndpoint(): void
    {
        $em = $this->getEntityManager();

        $testUser = $em->getRDBRepositoryByClass(User::class)->getNew()
            ->setUserName('test-0')
            ->setLastName('Test User 0');

        $em->saveEntity($testUser);

        $team = $em->getRDBRepositoryByClass(Team::class)->getNew();
        $em->saveEntity($team);

        $apiUser = $this->createUser([
            User::FIELD_USER_NAME => 'api',
            User::LINK_TEAMS . 'Ids' => [$team->getId()],
            User::FIELD_TYPE => User::TYPE_API,
            User::FIELD_AUTH_METHOD => User::AUTH_METHOD_API_KEY,
            User::FIELD_API_KEY => self::TEST_API_KEY,
        ], [
            Role::FIELD_DATA => [
                Scope::MCP => true,
                Account::ENTITY_TYPE => [
                    Table::ACTION_READ => Table::LEVEL_ALL,
                ],
                Lead::ENTITY_TYPE => [
                    Table::ACTION_READ => Table::LEVEL_TEAM,
                ],
                Opportunity::ENTITY_TYPE => [
                    Table::ACTION_READ => Table::LEVEL_ALL,
                ],
                Task::ENTITY_TYPE => [
                    Table::ACTION_READ => Table::LEVEL_ALL,
                ],
                Call::ENTITY_TYPE => [
                    Table::ACTION_READ => Table::LEVEL_NO,
                ],
            ],
            Role::FIELD_FIELD_DATA => [
                Lead::ENTITY_TYPE => [
                    'description' => [
                        Table::ACTION_READ => Table::LEVEL_NO,
                        Table::ACTION_EDIT => Table::LEVEL_NO,
                    ]
                ],
            ],
        ]);

        $endpoint = $em->getRDBRepositoryByClass(Endpoint::class)->getNew()
            ->setName('Test')
            ->setSlug('test');
        $em->saveEntity($endpoint);

        $em->getRelation($endpoint, Endpoint::LINK_USERS)->relate($apiUser);

        $em->saveEntity(
            $em->getRDBRepositoryByClass(Feature::class)->getNew()
                ->setType(FindData::TYPE)
                ->setData(
                    new FindData(
                        entityType: Lead::ENTITY_TYPE,
                        textFilter: true,
                        selectFields: [
                            new FindData\Field(Field::NAME, 'Lead name.'),
                            new FindData\Field('accountName'),
                            new FindData\Field('emailAddress'),
                            new FindData\Field('status'),
                            new FindData\Field('description'),
                            new FindData\Field('teams'),
                        ],
                        primaryFilters: ['actual'],
                        boolFilters: ['onlyMy'],
                        filterFields: [
                            new FindData\Field('status'),
                            new FindData\Field('name'),
                            new FindData\Field('assignedUser'),
                            new FindData\Field('teams'),
                            new FindData\Field('emailAddress'),
                        ],
                    )
                )
                ->setEndpoint($endpoint)
        );

        $em->saveEntity(
            $em->getRDBRepositoryByClass(Feature::class)->getNew()
                ->setType(FindData::TYPE)
                ->setData(
                    new FindData(
                        entityType: Task::ENTITY_TYPE,
                        textFilter: true,
                        selectFields: [
                            new FindData\Field(Field::NAME),
                            new FindData\Field(Field::PARENT),
                            new FindData\Field('dateStart'),
                            new FindData\Field('dateEnd'),
                        ],
                        primaryFilters: ['actual'],
                        boolFilters: ['onlyMy'],
                        filterFields: [
                            new FindData\Field('dateStart'),
                            new FindData\Field(Field::PARENT),
                        ],
                    )
                )
                ->setEndpoint($endpoint)
        );

        $em->saveEntity(
            $em->getRDBRepositoryByClass(Feature::class)->getNew()
                ->setType(FindData::TYPE)
                ->setData(
                    new FindData(
                        entityType: Opportunity::ENTITY_TYPE,
                        textFilter: true,
                        selectFields: [
                            new FindData\Field(Field::NAME),
                            new FindData\Field(Opportunity::FIELD_STAGE),
                            new FindData\Field(Opportunity::FIELD_CLOSE_DATE),
                        ],
                        primaryFilters: ['actual'],
                        boolFilters: ['onlyMy'],
                        filterFields: [
                            new FindData\Field(Opportunity::FIELD_CLOSE_DATE),
                        ],
                    )
                )
                ->setEndpoint($endpoint)
        );

        // No access.
        $em->saveEntity(
            $em->getRDBRepositoryByClass(Feature::class)->getNew()
                ->setType(FindData::TYPE)
                ->setData(
                    new FindData(
                        entityType: Call::ENTITY_TYPE,
                        textFilter: true,
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

        $request = $this->createEntryRequest(
            method: Method::SERVER_DISCOVER,
            slug: 'test',
            jsonrpc: '1.0',
        );

        $this->authenticate(
            method: ApiKey::NAME,
            request: $request,
        );

        $apiAction = $this->getInjectableFactory()->create(PostEntry::class);

        // Unsupported JSON-RPC.

        $response = $apiAction->process(
            $this->createEntryRequest(
                method: Method::SERVER_DISCOVER,
                slug: 'test',
                jsonrpc: '1.0',
            )
        );

        $body = Json::decode($response->getBody());

        $this->assertEquals(-32600, $body->error->code);
        $this->assertEquals(200, $response->getStatusCode());

        // Unsupported protocol version.

        $response = $apiAction->process(
            $this->createEntryRequest(
                method: Method::SERVER_DISCOVER,
                slug: 'test',
                id: 1,
                protocolVersion: '1970-01-01',
            )
        );

        $body = Json::decode($response->getBody());

        $this->assertEquals(1, $body->id);
        $this->assertEquals(-32022, $body->error->code);
        $this->assertEquals(400, $response->getStatusCode());

        // Discover.

        $response = $apiAction->process(
            $this->createEntryRequest(
                method: Method::SERVER_DISCOVER,
                slug: 'test',
                id: 'A1',
            )
        );

        $body = Json::decode($response->getBody());

        $this->assertEquals('A1', $body->id);
        $this->assertEquals('complete', $body->result?->resultType);
        $this->assertEquals(['2026-07-28'], $body->result?->supportedVersions);
        $this->assertEquals('private', $body->result?->cacheScope);
        $this->assertEquals((object) [
            'tools' => (object) [
                'listChanged' => false,
            ],
        ], $body->result?->capabilities);

        // Tools list.

        $response = $apiAction->process(
            $this->createEntryRequest(
                method: Method::TOOLS_LIST,
                slug: 'test',
                id: 1,
            )
        );

        $body = Json::decode($response->getBody());

        $this->assertEquals(1, $body->id);
        $this->assertEquals('complete', $body->result?->resultType);
        $this->assertEquals('private', $body->result?->cacheScope);

        $tools = $body->result->tools;

        $this->assertIsArray($tools);

        $findLeadToolIndex = array_find_key($tools, fn ($it) => $it->name === 'Find.Lead');
        $this->assertNotNull($findLeadToolIndex);

        $findLeadTool = $tools[$findLeadToolIndex];

        $this->assertNull(
            array_find_key($tools, fn ($it) => $it->name === 'Find.Call')
        );

        $this->assertEquals('Find.Lead', $findLeadTool->name);
        $this->assertEquals('https://json-schema.org/draft/2020-12/schema', $tools[0]->inputSchema->{'$schema'});
        $this->assertEquals('https://json-schema.org/draft/2020-12/schema', $tools[0]->outputSchema->{'$schema'});

        $this->assertEquals('object', $tools[0]->inputSchema->type);

        $this->assertEquals('string', $tools[0]->inputSchema->properties->textFilter->type);
        $this->assertCount(4, $tools[0]->inputSchema->properties->orderBy->anyOf);
        $this->assertCount(2, $tools[0]->inputSchema->properties->primaryFilter->anyOf);
        $this->assertCount(1, $tools[0]->inputSchema->properties->boolFilterList->items->anyOf);
        $this->assertEquals('array', $tools[0]->inputSchema->properties->where->type);

        $this->assertEquals((object) [
            'anyOf' => [
                (object) [
                    'const' => 'asc',
                    'description' => 'Ascending order.',
                ],
                (object) [
                    'const' => 'desc',
                    'description' => 'Descending order.',
                ],
            ],
            'description' => 'Sorting direction.',
        ], $tools[0]->inputSchema->properties->order);

        $this->assertEquals('object', $tools[0]->outputSchema->type);
        $this->assertEquals('array', $tools[0]->outputSchema->properties->list->type);
        $this->assertEquals('integer', $tools[0]->outputSchema->properties->total->type);

        //

        $this->createRecords(
            team: $team,
            user: $testUser,
        );

        // Call `Find.Lead`. Primary filter.

        $response = $apiAction->process(
            $this->createEntryRequest(
                method: Method::TOOLS_CALL,
                slug: 'test',
                id: 1,
                params: (object) [
                    'name' => 'Find.Lead',
                    'arguments' => (object) [
                        'primaryFilter' => 'actual',
                        'selectFields' => [
                            'name',
                            'status',
                            'emailAddress',
                            'teams',
                        ],
                    ],
                ],
            )
        );

        $body = Json::decode($response->getBody());

        $this->assertEquals(1, $body->id);
        $this->assertEquals('complete', $body->result?->resultType);
        $this->assertEquals(1, $body->result->structuredContent->total);
        $this->assertIsArray($body->result->structuredContent->list);
        $this->assertObjectHasProperty('id', $body->result->structuredContent->list[0]);
        $this->assertObjectNotHasProperty('campaignId', $body->result->structuredContent->list[0]);
        $this->assertObjectNotHasProperty('description', $body->result->structuredContent->list[0]);
        $this->assertObjectNotHasProperty('source', $body->result->structuredContent->list[0]);
        $this->assertEquals('test1@test.com', $body->result->structuredContent->list[0]->emailAddress);
        $this->assertEquals(Lead::STATUS_NEW, $body->result->structuredContent->list[0]->status);
        $this->assertEquals([$team->getId()], $body->result->structuredContent->list[0]->teamsIds);

        $this->createJsonSchemaValidator()->assert(
            $this->getToolEnvelope($endpoint, 'Find.Lead')->tool->outputSchema,
            $body->result->structuredContent
        );

        // Call `Find.Lead`. Where clause, text filter.

        $response = $apiAction->process(
            $this->createEntryRequest(
                method: Method::TOOLS_CALL,
                slug: 'test',
                id: 1,
                params: (object) [
                    'name' => 'Find.Lead',
                    'arguments' => (object) [
                        'textFilter' => 'Test*',
                        'offset' => 0,
                        'maxSize' => 5,
                        'where' => [
                            (object) [
                                'type' => Type::IN,
                                'attribute' => 'status',
                                'value' => [Lead::STATUS_CONVERTED],
                            ],
                            (object) [
                                'type' => Type::IS_NOT_NULL,
                                'attribute' => 'status',
                            ],
                            (object) [
                                'type' => Type::EQUALS,
                                'attribute' => 'name',
                                'value' => 'Test 3',
                            ],
                            (object) [
                                'type' => Type::EQUALS,
                                'attribute' => 'assignedUserId',
                                'value' => $testUser->getId(),
                            ],
                            (object) [
                                'type' => Type::EQUALS,
                                'attribute' => 'emailAddress',
                                'value' => 'test3@test.com',
                            ],
                            (object) [
                                'type' => Type::IS_LINKED_WITH,
                                'attribute' => 'teams',
                                'value' => [$team->getId()],
                            ],
                            (object) [
                                'type' => Type::IS_LINKED_WITH_ANY,
                                'attribute' => 'teams',
                                'value' => [$team->getId()],
                            ],
                        ],
                    ],
                ],
            )
        );

        $body = Json::decode($response->getBody());

        $this->assertEquals('complete', $body->result?->resultType);
        $this->assertEquals(1, $body->result->structuredContent->total);
        $this->assertEquals(Lead::STATUS_CONVERTED, $body->result->structuredContent->list[0]->status);

        $this->processValidateJsonSchema($endpoint, 'Find.Lead', $body->result->structuredContent);

        // Call `Find.Task`. Offset, order.

        $response = $apiAction->process(
            $this->createEntryRequest(
                method: Method::TOOLS_CALL,
                slug: 'test',
                id: 1,
                params: (object) [
                    'name' => 'Find.Task',
                    'arguments' => (object) [
                        'textFilter' => 'Test*',
                        'offset' => 1,
                        'maxSize' => 2,
                        'orderBy' => 'name',
                        'order' => 'desc',
                    ],
                ],
            )
        );

        $body = Json::decode($response->getBody());

        $this->assertEquals('complete', $body->result?->resultType);
        $this->assertEquals(3, $body->result->structuredContent->total);
        $this->assertCount(2, $body->result->structuredContent->list);
        $this->assertEquals('Test 2', $body->result->structuredContent->list[0]->name);
        $this->assertEquals('Test 1', $body->result->structuredContent->list[1]->name);

        $this->processValidateJsonSchema($endpoint, 'Find.Task', $body->result->structuredContent);

        // Call `Find.Task`. Where.

        $lead = $em->getRDBRepositoryByClass(Lead::class)
            ->where([Field::NAME => 'Test 1'])
            ->findOne();

        $this->assertNotNull($lead);

        $response = $apiAction->process(
            $this->createEntryRequest(
                method: Method::TOOLS_CALL,
                slug: 'test',
                id: 1,
                params: (object) [
                    'name' => 'Find.Task',
                    'arguments' => (object) [
                        'orderBy' => 'name',
                        'order' => 'desc',
                        'where' => [
                            (object) [
                                'type' => Type::EQUALS,
                                'attribute' => 'parentType',
                                'value' => Lead::ENTITY_TYPE,
                            ],
                            (object) [
                                'type' => Type::EQUALS,
                                'attribute' => 'parentId',
                                'value' => $lead->getId(),
                            ],
                            (object) [
                                'type' => Type::ON,
                                'attribute' => 'dateStart',
                                'value' => '2030-01-01',
                                'dateTime' => true,
                            ],
                        ],
                    ],
                ],
            ),
        );

        $body = Json::decode($response->getBody());

        $this->assertEquals('complete', $body->result?->resultType);
        $this->assertEquals(1, $body->result->structuredContent->total);

        $this->processValidateJsonSchema($endpoint, 'Find.Task', $body->result->structuredContent);

        // Call `Find.Opportunity`. Where.

        $response = $apiAction->process(
            $this->createEntryRequest(
                method: Method::TOOLS_CALL,
                slug: 'test',
                id: 1,
                params: (object) [
                    'name' => 'Find.Opportunity',
                    'arguments' => (object) [
                        'where' => [
                            (object) [
                                'type' => Type::ON,
                                'attribute' => Opportunity::FIELD_CLOSE_DATE,
                                'value' => '2030-01-01',
                            ],
                        ],
                    ],
                ],
            ),
        );

        $body = Json::decode($response->getBody());

        $this->assertEquals('complete', $body->result?->resultType);
        $this->assertEquals(1, $body->result->structuredContent->total);

        $this->processValidateJsonSchema($endpoint, 'Find.Opportunity', $body->result->structuredContent);
    }

    private function createRecords(
        Team $team,
        User $user,
    ): void  {
        $em = $this->getEntityManager();

        $em->saveEntity(
            $em->getRDBRepositoryByClass(Opportunity::class)->getNew()
                ->setName('Test 1')
                ->setCloseDate(Date::fromString('2030-01-01'))
                ->setStage(Opportunity::STAGE_CLOSED_WON)
        );

        $lead1 = $em->getRDBRepositoryByClass(Lead::class)->getNew()
            ->setTeams(LinkMultiple::create()->withAddedId($team->getId()))
            ->setLastName('Test 1')
            ->setEmailAddressGroup(EmailAddressGroup::create([EmailAddress::create('test1@test.com')]))
            ->setStatus(Lead::STATUS_NEW);

        // Visible to the user.
        $em->saveEntity($lead1);

        // No team.
        $em->saveEntity(
            $em->getRDBRepositoryByClass(Lead::class)->getNew()
                ->setLastName('Test 2')
                ->setStatus(Lead::STATUS_NEW)
        );

        // Visible to the user, not actual.
        $em->saveEntity(
            $em->getRDBRepositoryByClass(Lead::class)->getNew()
                ->setTeams(LinkMultiple::create()->withAddedId($team->getId()))
                ->setLastName('Test 3')
                ->setStatus(Lead::STATUS_CONVERTED)
                ->setEmailAddressGroup(EmailAddressGroup::create([EmailAddress::create('test3@test.com')]))
                ->setAssignedUser($user)
        );

        $em->saveEntity(
            $em->getRDBRepositoryByClass(Task::class)->getNew()
                ->setName('Test 1')
                ->setDateStart(DateTimeOptional::fromString('2030-01-01'))
                ->setParent($lead1)
                ->setStatus(Task::STATUS_STARTED)
        );

        $em->saveEntity(
            $em->getRDBRepositoryByClass(Task::class)->getNew()
                ->setName('Test 2')
                ->setDateStart(DateTimeOptional::fromString('2030-01-02 10:00'))
                ->setStatus(Task::STATUS_STARTED)
        );

        $em->saveEntity(
            $em->getRDBRepositoryByClass(Task::class)->getNew()
                ->setName('Test 3')
                ->setDateStart(DateTimeOptional::fromString('2030-01-01'))
                ->setStatus(Task::STATUS_COMPLETED)
        );
    }

    /**
     * @noinspection PhpUnhandledExceptionInspection
     */
    private function createEntryRequest(
        ?string $method,
        string $slug,
        ?string $jsonrpc = '2.0',
        ?string $id = null,
        ?string $protocolVersion = '2026-07-28',
        mixed $params = null,
    ): RequestWrapper {

        $body = [
            'method' => $method,
        ];

        if ($method !== null) {
            $body['method'] = $method;
        }

        if ($id !== null) {
            $body['id'] = $id;
        }

        if ($jsonrpc !== null) {
            $body['jsonrpc'] = $jsonrpc;
        }

        if ($params !== null) {
            $body['params'] = $params;
        }

        return $this->createRequest(
            method: 'POST',
            headers: [
                'Content-Type' => 'application/json',
                'X-Api-Key' => self::TEST_API_KEY,
                'MCP-Protocol-Version' => $protocolVersion,
            ],
            body: Json::encode($body),
            routeParams: [
                'slug' => $slug,
            ],
        );
    }

    /**
     * @noinspection PhpUnhandledExceptionInspection
     */
    private function getToolEnvelope(Endpoint $endpoint, string $toolName): ToolEnvelope
    {
        $toolProvider = $this->getInjectableFactory()->createWithBinding(
            ToolProvider::class,
            BindingContainerBuilder::create()
                ->bindInstance(Endpoint::class, $endpoint)
                ->build()
        );

        return $toolProvider->get($toolName);
    }


    private function createJsonSchemaValidator(): Validator
    {
        return $this->getInjectableFactory()->create(Validator::class);
    }

    private function processValidateJsonSchema(Endpoint $endpoint, string $name, $structuredContent): void
    {
        try {
            $this->createJsonSchemaValidator()->assert(
                $this->getToolEnvelope($endpoint, $name)->tool->outputSchema,
                $structuredContent
            );
        } catch (InvalidParamsError $e) {
            throw new RuntimeException("Validation error. " . var_export($e->getData(), true));
        }
    }
}
