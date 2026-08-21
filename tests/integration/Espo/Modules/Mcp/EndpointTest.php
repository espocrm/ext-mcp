<?php
/**LICENSE**/

namespace integration\Espo\Modules\Mcp;

use DateTimeInterface;
use Espo\Core\Acl\Permission;
use Espo\Core\Acl\Table;
use Espo\Core\Api\RequestWrapper;
use Espo\Core\Authentication\Logins\ApiKey;
use Espo\Core\Binding\BindingContainerBuilder;
use Espo\Core\Field\Currency;
use Espo\Core\Field\Date;
use Espo\Core\Field\DateTime;
use Espo\Core\Field\DateTimeOptional;
use Espo\Core\Field\EmailAddress;
use Espo\Core\Field\EmailAddressGroup;
use Espo\Core\Field\LinkMultiple;
use Espo\Core\Name\Field;
use Espo\Core\Select\Where\Item\Type;
use Espo\Core\Utils\Config\ConfigWriter;
use Espo\Core\Utils\Json;
use Espo\Entities\Role;
use Espo\Entities\Team;
use Espo\Entities\User;
use Espo\Modules\Crm\Entities\Account;
use Espo\Modules\Crm\Entities\Call;
use Espo\Modules\Crm\Entities\Lead;
use Espo\Modules\Crm\Entities\Meeting;
use Espo\Modules\Crm\Entities\Opportunity;
use Espo\Modules\Crm\Entities\Task;
use Espo\Modules\Mcp\Entities\Endpoint;
use Espo\Modules\Mcp\Entities\Feature;
use Espo\Modules\Mcp\Tools\Feature\Create\CreateData;
use Espo\Modules\Mcp\Tools\Feature\Delete\DeleteData;
use Espo\Modules\Mcp\Tools\Feature\Find\FindData;
use Espo\Modules\Mcp\Tools\Feature\Read\ReadData;
use Espo\Modules\Mcp\Tools\Feature\Update\UpdateData;
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

    public function testEndpoint(): void
    {
        $this->configureApp();

        $team = $this->createTeam();
        $testUser = $this->createTestUser(team: $team);
        $apiUser = $this->createApiUser(team: $team);

        $endpoint = $this->createEndpoint($apiUser);

        //

        $this->createFeatures(
            endpoint: $endpoint,
        );

        $this->createRecords(
            team: $team,
            user: $testUser,
        );

        //

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

        //

        $this->processTestDiscover(
            apiAction: $apiAction,
        );

        $this->processTestToolsList(
            apiAction: $apiAction,
        );

        $this->processTestFind(
             apiAction: $apiAction,
             team: $team,
             endpoint: $endpoint,
             testUser: $testUser,
        );

        $this->processTestRead(
            apiAction: $apiAction,
            endpoint: $endpoint,
            team: $team,
        );

        $this->processTestCreate(
            apiAction: $apiAction,
            endpoint: $endpoint,
            team: $team,
        );

        $this->processTestUpdate(
            apiAction: $apiAction,
            endpoint: $endpoint,
        );

        $this->processTestDelete(
            apiAction: $apiAction,
            endpoint: $endpoint,
        );
    }

    private function createRecords(
        Team $team,
        User $user,
    ): void  {

        $em = $this->getEntityManager();

        $account1 = $em->getRDBRepositoryByClass(Account::class)->getNew()
            ->setName('Test 1');

        $em->saveEntity($account1);

        // Normal Opportunity.
        $em->saveEntity(
            $em->getRDBRepositoryByClass(Opportunity::class)->getNew()
                ->setName('Test 1')
                ->setCloseDate(Date::fromString('2030-01-01'))
                ->setAmount(Currency::create(100, 'USD'))
                ->setStage(Opportunity::STAGE_CLOSED_WON)
                ->setTeams(LinkMultiple::create()->withAddedId($team->getId()))
                ->setAccount($account1)
        );

        // Normal without amount.
        $em->saveEntity(
            $em->getRDBRepositoryByClass(Opportunity::class)->getNew()
                ->setName('Test 2')
                ->setCloseDate(Date::fromString('2030-01-01'))
                ->setStage(Opportunity::STAGE_CLOSED_WON)
        );

        // Visible to the user.
        $lead1 = $em->getRDBRepositoryByClass(Lead::class)->getNew()
            ->setTeams(LinkMultiple::create()->withAddedId($team->getId()))
            ->setLastName('Test 1')
            ->setEmailAddressGroup(EmailAddressGroup::create([EmailAddress::create('test1@test.com')]))
            ->setStatus(Lead::STATUS_NEW);
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

        //

        $em->saveEntity(
            $em->getRDBRepositoryByClass(Call::class)->getNew()
                ->setName('Test 1')
                ->setDateStart(DateTime::fromString('2030-01-01 10:00'))
                ->setDateEnd(DateTime::fromString('2030-01-01 10:30'))
        );
    }

    /**
     * @noinspection PhpUnhandledExceptionInspection
     * @noinspection PhpSameParameterValueInspection
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

    private function createFeatures(Endpoint $endpoint): void
    {
        $em = $this->getEntityManager();

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
                            new FindData\Field(Opportunity::FIELD_AMOUNT),
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

        $em->saveEntity(
            $em->getRDBRepositoryByClass(Feature::class)->getNew()
                ->setType(ReadData::TYPE)
                ->setData(
                    new ReadData(
                        entityType: Lead::ENTITY_TYPE,
                        selectFields: [
                            new FindData\Field(Field::NAME, 'Lead name.'),
                            new FindData\Field('accountName'),
                            new FindData\Field('emailAddress'),
                            new FindData\Field('status'),
                            new FindData\Field('description'),
                            new FindData\Field('teams'),
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
                        entityType: Opportunity::ENTITY_TYPE,
                        selectFields: [
                            new FindData\Field(Field::NAME),
                            new FindData\Field('account'),
                            new FindData\Field('stage'),
                            new FindData\Field('assignedUser'),
                            new FindData\Field('description'),
                            new FindData\Field('amount'),
                            new FindData\Field('amountConverted'),
                            new FindData\Field('teams'),
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
                        entityType: Meeting::ENTITY_TYPE,
                        selectFields: [
                            new FindData\Field(Field::NAME),
                            new FindData\Field('parent'),
                            new FindData\Field('dateStart'),
                            new FindData\Field('dateEnd'),
                            new FindData\Field('duration'),
                            new FindData\Field('description'),
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
                        entityType: Call::ENTITY_TYPE,
                        selectFields: [
                            new FindData\Field(Field::NAME),
                            new FindData\Field('parent'),
                            new FindData\Field('dateStart'),
                            new FindData\Field('dateEnd'),
                            new FindData\Field('duration'),
                            new FindData\Field('description'),
                            new FindData\Field('assignedUser'),
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
                            new FindData\Field('firstName'),
                            new FindData\Field('lastName'),
                            new FindData\Field('emailAddress'),
                            new FindData\Field('phoneNumber'),
                            new FindData\Field('status'),
                            new FindData\Field('description'),
                            new FindData\Field('teams'),
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
                        entityType: Opportunity::ENTITY_TYPE,
                        writeFields: [
                            new FindData\Field('name'),
                            new FindData\Field('amount'),
                            new FindData\Field('description'),
                            new FindData\Field(Opportunity::FIELD_CLOSE_DATE),
                            new FindData\Field(Field::ASSIGNED_USER),
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
                        entityType: Task::ENTITY_TYPE,
                        writeFields: [
                            new FindData\Field('name'),
                            new FindData\Field('dateStart'),
                            new FindData\Field('dateEnd'),
                            new FindData\Field('status'),
                            new FindData\Field('description'),
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
                        entityType: Meeting::ENTITY_TYPE,
                        writeFields: [
                            new FindData\Field('name'),
                            new FindData\Field('dateStart'),
                            new FindData\Field('dateEnd'),
                            new FindData\Field('status'),
                            new FindData\Field('description'),
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
                        entityType: Call::ENTITY_TYPE,
                        writeFields: [
                            new FindData\Field('name'),
                            new FindData\Field('dateStart'),
                            new FindData\Field('dateEnd'),
                            new FindData\Field('duration'),
                            new FindData\Field('parent'),
                            new FindData\Field('status'),
                            new FindData\Field('description'),
                            new FindData\Field('assignedUser'),
                        ],
                    )
                )
                ->setEndpoint($endpoint)
        );

        $em->saveEntity(
            $em->getRDBRepositoryByClass(Feature::class)->getNew()
                ->setType(UpdateData::TYPE)
                ->setData(
                    new CreateData(
                        entityType: Lead::ENTITY_TYPE,
                        writeFields: [
                            new FindData\Field('firstName'),
                            new FindData\Field('lastName'),
                            new FindData\Field('emailAddress'),
                            new FindData\Field('phoneNumber'),
                            new FindData\Field('status'),
                            new FindData\Field('description'),
                            new FindData\Field('teams'),
                        ],
                    )
                )
                ->setEndpoint($endpoint)
        );

        $em->saveEntity(
            $em->getRDBRepositoryByClass(Feature::class)->getNew()
                ->setType(UpdateData::TYPE)
                ->setData(
                    new CreateData(
                        entityType: Meeting::ENTITY_TYPE,
                        writeFields: [
                            new FindData\Field('name'),
                            new FindData\Field('dateStart'),
                            new FindData\Field('description'),
                        ],
                    )
                )
                ->setEndpoint($endpoint)
        );

        $em->saveEntity(
            $em->getRDBRepositoryByClass(Feature::class)->getNew()
                ->setType(DeleteData::TYPE)
                ->setData(
                    new DeleteData(
                        entityType: Lead::ENTITY_TYPE,
                    )
                )
                ->setEndpoint($endpoint)
        );

        $em->saveEntity(
            $em->getRDBRepositoryByClass(Feature::class)->getNew()
                ->setType(DeleteData::TYPE)
                ->setData(
                    new DeleteData(
                        entityType: Meeting::ENTITY_TYPE,
                    )
                )
                ->setEndpoint($endpoint)
        );
    }

    /**
     * @noinspection PhpUnhandledExceptionInspection
     */
    private function processTestDiscover(PostEntry $apiAction): void
    {
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

        //

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

        $this->assertEquals('EspoCRM.test', $body->result->_meta->{"io.modelcontextprotocol/serverInfo"}->name);
        $this->assertEquals('Test', $body->result->_meta->{"io.modelcontextprotocol/serverInfo"}->title);
        $this->assertEquals('Test.', $body->result->_meta->{"io.modelcontextprotocol/serverInfo"}->description);
    }

    /**
     * @noinspection PhpUnhandledExceptionInspection
     */
    private function processTestToolsList(PostEntry $apiAction): void
    {
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

        //

        $findLeadToolIndex = array_find_key($tools, fn ($it) => $it->name === 'Find.Lead');
        $this->assertNotNull($findLeadToolIndex);
        $findLeadTool = $tools[$findLeadToolIndex] ?? null;
        $this->assertNotNull($findLeadTool);

        $this->assertEquals('Find.Lead', $findLeadTool->name);
        $this->assertEquals('https://json-schema.org/draft/2020-12/schema', $findLeadTool->inputSchema->{'$schema'});
        $this->assertEquals('https://json-schema.org/draft/2020-12/schema', $findLeadTool->outputSchema->{'$schema'});

        $this->assertTrue(str_contains($findLeadTool->inputSchema->properties->orderBy->description, 'Created At'));

        $this->assertEquals('object', $findLeadTool->inputSchema->type);

        $this->assertEquals('string', $findLeadTool->inputSchema->properties->textFilter->type);
        $this->assertCount(4, $findLeadTool->inputSchema->properties->orderBy->anyOf);
        $this->assertCount(2, $findLeadTool->inputSchema->properties->primaryFilter->anyOf);
        $this->assertCount(1, $findLeadTool->inputSchema->properties->boolFilterList->items->anyOf);
        $this->assertEquals('array', $findLeadTool->inputSchema->properties->where->type);

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
        ], $findLeadTool->inputSchema->properties->order);

        $this->assertEquals('object', $findLeadTool->outputSchema->type);
        $this->assertEquals('array', $findLeadTool->outputSchema->properties->records->type);
        $this->assertEquals('integer', $findLeadTool->outputSchema->properties->total->type);

        //

        $readLeadToolIndex = array_find_key($tools, fn ($it) => $it->name === 'Read.Lead');
        $this->assertNotNull($readLeadToolIndex);
        $readLeadTool = $tools[$readLeadToolIndex] ?? null;
        $this->assertNotNull($readLeadTool);

        $this->assertEquals('string', $readLeadTool->inputSchema->properties->id->type);
        $this->assertEquals('array', $readLeadTool->inputSchema->properties->selectFields->type);

        //

        $createLeadToolIndex = array_find_key($tools, fn ($it) => $it->name === 'Create.Lead');
        $this->assertNotNull($createLeadToolIndex);
        $createLeadTool = $tools[$createLeadToolIndex] ?? null;
        $this->assertNotNull($createLeadTool);

        $this->assertObjectHasProperty('emailAddress', $createLeadTool->inputSchema->properties->record->properties);
        // No field-level access.
        $this->assertObjectNotHasProperty('description', $createLeadTool->inputSchema->properties->record->properties);

        //

        $createMeetingToolIndex = array_find_key($tools, fn ($it) => $it->name === 'Create.Meeting');
        $this->assertNull($createMeetingToolIndex);

        //

        $createCallToolIndex = array_find_key($tools, fn ($it) => $it->name === 'Create.Call');
        $this->assertNotNull($createCallToolIndex);
        $createCallTool = $tools[$createCallToolIndex] ?? null;
        $this->assertNotNull($createCallTool);

        $this->assertContains('dateStart', $createCallTool->inputSchema->properties->record->required);
        $this->assertContains('name', $createCallTool->inputSchema->properties->record->required);
        $this->assertContains('assignedUserId', $createCallTool->inputSchema->properties->record->required);

        //

        $updateLeadToolIndex = array_find_key($tools, fn ($it) => $it->name === 'Update.Lead');
        $this->assertNotNull($updateLeadToolIndex);
        $updateLeadTool = $tools[$updateLeadToolIndex] ?? null;
        $this->assertNotNull($updateLeadTool);

        $this->assertObjectHasProperty('teamsIds', $updateLeadTool->inputSchema->properties->record->properties);

        //

        $updateMeetingToolIndex = array_find_key($tools, fn ($it) => $it->name === 'Update.Meeting');
        $this->assertNull($updateMeetingToolIndex);
    }

    /**
     * @noinspection PhpUnhandledExceptionInspection
     */
    private function processTestFind(PostEntry $apiAction, Team $team, Endpoint $endpoint, User $testUser): void
    {
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
        $this->assertIsArray($body->result->structuredContent->records);
        $this->assertObjectHasProperty('id', $body->result->structuredContent->records[0]);
        $this->assertObjectNotHasProperty('campaignId', $body->result->structuredContent->records[0]);
        $this->assertObjectNotHasProperty('description', $body->result->structuredContent->records[0]);
        $this->assertObjectNotHasProperty('source', $body->result->structuredContent->records[0]);
        $this->assertEquals('test1@test.com', $body->result->structuredContent->records[0]->emailAddress);
        $this->assertEquals(Lead::STATUS_NEW, $body->result->structuredContent->records[0]->status);
        $this->assertEquals([$team->getId()], $body->result->structuredContent->records[0]->teamsIds);

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
        $this->assertEquals(Lead::STATUS_CONVERTED, $body->result->structuredContent->records[0]->status);

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
        $this->assertCount(2, $body->result->structuredContent->records);
        $this->assertEquals('Test 2', $body->result->structuredContent->records[0]->name);
        $this->assertEquals('Test 1', $body->result->structuredContent->records[1]->name);

        $this->processValidateJsonSchema($endpoint, 'Find.Task', $body->result->structuredContent);

        //

        $lead1 = $this->getLead('Test 1');
        $lead2 = $this->getLead('Test 2');

        $this->assertNotNull($lead1);
        $this->assertNotNull($lead2);

        // Call `Find.Task`. Where.

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
                                'value' => $lead1->getId(),
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
                        'orderBy' => 'name',
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
        $this->assertEquals(2, $body->result->structuredContent->total);

        $this->processValidateJsonSchema($endpoint, 'Find.Opportunity', $body->result->structuredContent);

        //

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
                        'where' => [
                            (object) [
                                'attribute' => 'teams',
                                'type' => Type::IS_LINKED_WITH,
                                'value' => ['non-existing-id'],
                            ],
                        ]
                    ],
                ],
            )
        );

        $body = Json::decode($response->getBody());

        $this->assertTrue($body->result->isError);
        $this->assertEquals(400, $body->result->structuredContent->error->code);
    }

    private function getLead(string $name): ?Lead
    {
        return $this->getEntityManager()
            ->getRDBRepositoryByClass(Lead::class)
            ->where([Field::NAME => $name])
            ->findOne();
    }


    /**
     * @noinspection PhpUnhandledExceptionInspection
     */
    private function processTestRead(PostEntry $apiAction, Endpoint $endpoint, Team $team): void
    {
        $em = $this->getEntityManager();

        $lead1 = $this->getLead('Test 1');
        $lead2 = $this->getLead('Test 2');

        // Call Read.Lead.

        $response = $apiAction->process(
            $this->createEntryRequest(
                method: Method::TOOLS_CALL,
                slug: 'test',
                id: 1,
                params: (object) [
                    'name' => 'Read.Lead',
                    'arguments' => (object) [
                        'id' => $lead1->getId(),
                    ],
                ],
            ),
        );

        $body = Json::decode($response->getBody());

        $this->assertEquals('complete', $body->result?->resultType);
        $this->assertEquals($lead1->getId(), $body->result->structuredContent->record->id);
        $this->assertObjectHasProperty('name', $body->result->structuredContent->record);
        $this->assertObjectHasProperty('status', $body->result->structuredContent->record);
        $this->assertObjectNotHasProperty('description', $body->result->structuredContent->record);

        $this->assertCount(1, $body->result->content);
        $this->assertEquals('resource_link', $body->result->content[0]->type);
        $this->assertEquals("http://localhost#Lead/view/{$lead1->getId()}", $body->result->content[0]->uri);

        $this->processValidateJsonSchema($endpoint, 'Read.Lead', $body->result->structuredContent);

        // Call Read.Lead. Select fields.

        $response = $apiAction->process(
            $this->createEntryRequest(
                method: Method::TOOLS_CALL,
                slug: 'test',
                id: 1,
                params: (object) [
                    'name' => 'Read.Lead',
                    'arguments' => (object) [
                        'id' => $lead1->getId(),
                        'selectFields' => [
                            'status',
                        ],
                    ],
                ],
            ),
        );

        $body = Json::decode($response->getBody());

        $this->assertEquals('complete', $body->result?->resultType);
        $this->assertEquals($lead1->getId(), $body->result->structuredContent->record->id);
        $this->assertObjectNotHasProperty('name', $body->result->structuredContent->record);
        $this->assertObjectHasProperty('status', $body->result->structuredContent->record);

        // Call Read.Lead. Not found error.

        $response = $apiAction->process(
            $this->createEntryRequest(
                method: Method::TOOLS_CALL,
                slug: 'test',
                id: 1,
                params: (object) [
                    'name' => 'Read.Lead',
                    'arguments' => (object) [
                        'id' => $lead2->getId(),
                    ],
                ],
            ),
        );

        $body = Json::decode($response->getBody());

        $this->assertEquals('complete', $body->result?->resultType);
        $this->assertObjectNotHasProperty('record', $body->result->structuredContent);
        $this->assertEquals(403, $body->result->structuredContent->error->code);

        // Call Read.Opportunity.

        $opportunity1 = $em->getRDBRepositoryByClass(Opportunity::class)
            ->where([Field::NAME => 'Test 1'])
            ->findOne();

        assert($opportunity1 !== null);

        $response = $apiAction->process(
            $this->createEntryRequest(
                method: Method::TOOLS_CALL,
                slug: 'test',
                id: 1,
                params: (object) [
                    'name' => 'Read.Opportunity',
                    'arguments' => (object) [
                        'id' => $opportunity1->getId(),
                    ],
                ],
            ),
        );

        $body = Json::decode($response->getBody());

        $this->assertIsObject($body->result->structuredContent->record);

        $this->assertEquals([$team->getId()], $body->result->structuredContent->record->teamsIds);
        $this->assertEquals($team->getName(), $body->result->structuredContent->record->teamsNames->{$team->getId()});
        $this->processValidateJsonSchema($endpoint, 'Read.Opportunity', $body->result->structuredContent);

        // Call Read.Meeting. No tool because no access.

        $response = $apiAction->process(
            $this->createEntryRequest(
                method: Method::TOOLS_CALL,
                slug: 'test',
                id: 1,
                params: (object) [
                    'name' => 'Read.Meeting',
                    'arguments' => (object) [
                        'id' => 'any',
                    ],
                ],
            ),
        );

        $body = Json::decode($response->getBody());

        $this->assertEquals(-32602, $body->error->code);

        // Call Read.Call.

        $call1 = $em->getRDBRepositoryByClass(Call::class)
            ->where([Field::NAME => 'Test 1'])
            ->findOne();

        assert($call1 !== null);

        $response = $apiAction->process(
            $this->createEntryRequest(
                method: Method::TOOLS_CALL,
                slug: 'test',
                id: 1,
                params: (object) [
                    'name' => 'Read.Call',
                    'arguments' => (object) [
                        'id' => $call1->getId(),
                    ],
                ],
            ),
        );

        $body = Json::decode($response->getBody());

        $this->assertIsObject($body->result->structuredContent->record);

        $this->assertEquals(1800, $body->result->structuredContent->record->duration);

        $this->processValidateJsonSchema($endpoint, 'Read.Call', $body->result->structuredContent);
    }

    /**
     * @noinspection PhpUnhandledExceptionInspection
     */
    private function processTestCreate(PostEntry $apiAction, Endpoint $endpoint, Team $team): void
    {
        // Call Create.Lead.

        $response = $apiAction->process(
            $this->createEntryRequest(
                method: Method::TOOLS_CALL,
                slug: 'test',
                id: 1,
                params: (object) [
                    'name' => 'Create.Lead',
                    'arguments' => (object) [
                        'record' => (object) [
                            'firstName' => 'Hello',
                            'lastName' => 'A 1',
                            'emailAddress' => 'hello@a1.test',
                            'phoneNumber' => '+15453535543',
                            'teamsIds' => [$team->getId()],
                        ],
                    ],
                ],
            ),
        );

        $body = Json::decode($response->getBody());

        $this->assertIsString($body->result->structuredContent->record?->id);

        $this->processValidateJsonSchema($endpoint, 'Create.Lead', $body->result->structuredContent);

        // Call Create.Lead. Duplicate detection.

        $response = $apiAction->process(
            $this->createEntryRequest(
                method: Method::TOOLS_CALL,
                slug: 'test',
                id: 1,
                params: (object) [
                    'name' => 'Create.Lead',
                    'arguments' => (object) [
                        'record' => (object) [
                            'emailAddress' => 'hello@a1.test',
                        ],
                    ],
                ],
            ),
        );

        $body = Json::decode($response->getBody());

        $this->assertObjectNotHasProperty('structuredContent', $body->result);

        $this->assertEquals('elicitation/create', $body->result->inputRequests->confirmDuplicate->method);
        $this->assertCount(1, $body->result->content);

        // Call Create.Lead. Duplicate skip confirmed.

        $response = $apiAction->process(
            $this->createEntryRequest(
                method: Method::TOOLS_CALL,
                slug: 'test',
                id: 1,
                params: (object) [
                    'name' => 'Create.Lead',
                    'arguments' => (object) [
                        'record' => (object) [
                            'emailAddress' => 'hello@a1.test',
                        ],
                    ],
                    'inputResponses' => (object) [
                        'confirmDuplicate' => (object) [
                            'action' => 'accept',
                        ],
                    ],
                ],
            ),
        );

        $body = Json::decode($response->getBody());

        $this->assertObjectHasProperty('structuredContent', $body->result);

        // Call Create.Lead. Validation error.

        $response = $apiAction->process(
            $this->createEntryRequest(
                method: Method::TOOLS_CALL,
                slug: 'test',
                id: 1,
                params: (object) [
                    'name' => 'Create.Lead',
                    'arguments' => (object) [
                        'record' => (object) [
                            'phoneNumber' => '000',
                        ],
                    ],
                ],
            ),
        );

        $body = Json::decode($response->getBody());

        $this->assertTrue($body->result->isError);
        $this->assertEquals(400, $body->result->structuredContent->error->code);
        $this->assertTrue(str_contains($body->result->structuredContent->error->message, 'Validation'));

        //

        // Call Create.Opportunity.

        $testUser = $this->getEntityManager()
            ->getRDBRepositoryByClass(User::class)
            ->where([User::FIELD_USER_NAME => 'test-0'])
            ->findOne();

        $response = $apiAction->process(
            $this->createEntryRequest(
                method: Method::TOOLS_CALL,
                slug: 'test',
                id: 1,
                params: (object) [
                    'name' => 'Create.Opportunity',
                    'arguments' => (object) [
                        'record' => (object) [
                            'name' => 'Test',
                            'amount' => 100.00,
                            'amountCurrency' => 'USD',
                            Opportunity::FIELD_CLOSE_DATE => '2030-01-01',
                            'assignedUserId' => $testUser->getId(),
                        ],
                    ],
                ],
            ),
        );

        $body = Json::decode($response->getBody());

        $this->assertObjectHasProperty('structuredContent', $body->result);

        $this->processValidateJsonSchema($endpoint, 'Create.Lead', $body->result->structuredContent);

        // Call Create.Task.

        $response = $apiAction->process(
            $this->createEntryRequest(
                method: Method::TOOLS_CALL,
                slug: 'test',
                id: 1,
                params: (object) [
                    'name' => 'Create.Task',
                    'arguments' => (object) [
                        'record' => (object) [
                            'name' => 'Test',
                            'dateEndDate' => '2030-01-01',
                            'status' => Task::STATUS_STARTED,
                        ],
                    ],
                ],
            ),
        );

        $body = Json::decode($response->getBody());

        $this->assertObjectHasProperty('structuredContent', $body->result);

        $this->processValidateJsonSchema($endpoint, 'Create.Lead', $body->result->structuredContent);

        // Call Create.Call.

        $lead1 = $this->getLead('Test 1');

        $response = $apiAction->process(
            $this->createEntryRequest(
                method: Method::TOOLS_CALL,
                slug: 'test',
                id: 1,
                params: (object) [
                    'name' => 'Create.Call',
                    'arguments' => (object) [
                        'record' => (object) [
                            'name' => 'Test',
                            'dateStart' => DateTime::fromString('2030-01-01 10:00')
                                ->toDateTime()
                                ->format(DateTimeInterface::RFC3339),
                            'duration' => 180,
                            'assignedUserId' => $testUser->getId(),
                            'parentId' => $lead1->getId(),
                            'parentType' => $lead1->getEntityType(),
                        ],
                    ],
                ],
            ),
        );

        $body = Json::decode($response->getBody());

        $this->assertObjectHasProperty('structuredContent', $body->result);

        $this->processValidateJsonSchema($endpoint, 'Create.Lead', $body->result->structuredContent);
    }

    /**
     * @noinspection PhpUnhandledExceptionInspection
     */
    private function processTestUpdate(PostEntry $apiAction, Endpoint $endpoint): void
    {
        // Call Update.Lead.

        $lead = $this->getLead('Test 1');

        $response = $apiAction->process(
            $this->createEntryRequest(
                method: Method::TOOLS_CALL,
                slug: 'test',
                id: 1,
                params: (object) [
                    'name' => 'Update.Lead',
                    'arguments' => (object) [
                        'id' => $lead->getId(),
                        'record' => (object) [
                            'emailAddress' => 'hello-changed@a1.test',
                        ],
                    ],
                ],
            ),
        );

        $body = Json::decode($response->getBody());

        $this->assertObjectHasProperty('structuredContent', $body->result);

        $lead = $this->getLead('Test 1');
        $this->assertEquals('hello-changed@a1.test', $lead->getEmailAddress());

        $this->processValidateJsonSchema($endpoint, 'Update.Lead', $body->result->structuredContent);
    }

    /**
     * @noinspection PhpUnhandledExceptionInspection
     */
    private function processTestDelete(PostEntry $apiAction, Endpoint $endpoint): void
    {
        // Call Delete.Lead.

        $lead = $this->getLead('Test 1');

        $response = $apiAction->process(
            $this->createEntryRequest(
                method: Method::TOOLS_CALL,
                slug: 'test',
                id: 1,
                params: (object) [
                    'name' => 'Delete.Lead',
                    'arguments' => (object) [
                        'id' => $lead->getId(),
                    ],
                ],
            ),
        );

        $body = Json::decode($response->getBody());

        $this->assertObjectHasProperty('structuredContent', $body->result);

        $this->processValidateJsonSchema($endpoint, 'Delete.Lead', $body->result->structuredContent);

        $lead = $this->getLead('Test 1');
        $this->assertNull($lead);
    }

    private function createEndpoint(User $apiUser): Endpoint
    {
        $em = $this->getEntityManager();

        $endpoint = $em->getRDBRepositoryByClass(Endpoint::class)->getNew()
            ->setName('Test')
            ->setSlug('test')
            ->setPublicDescription('Test.');
        $em->saveEntity($endpoint);

        $em->getRelation($endpoint, Endpoint::LINK_USERS)->relate($apiUser);

        return $endpoint;
    }

    private function createApiUser(Team $team): User
    {
        return $this->createUser([
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
                    Table::ACTION_CREATE => Table::LEVEL_YES,
                    Table::ACTION_READ => Table::LEVEL_TEAM,
                    Table::ACTION_EDIT => Table::LEVEL_TEAM,
                    Table::ACTION_DELETE => Table::LEVEL_TEAM,
                ],
                Opportunity::ENTITY_TYPE => [
                    Table::ACTION_CREATE => Table::LEVEL_YES,
                    Table::ACTION_READ => Table::LEVEL_ALL,
                ],
                Task::ENTITY_TYPE => [
                    Table::ACTION_CREATE => Table::LEVEL_YES,
                    Table::ACTION_READ => Table::LEVEL_ALL,
                ],
                Meeting::ENTITY_TYPE => [
                    Table::ACTION_CREATE => Table::LEVEL_NO,
                    Table::ACTION_READ => Table::LEVEL_NO,
                    Table::ACTION_EDIT => Table::LEVEL_NO,
                    Table::ACTION_DELETE => Table::LEVEL_NO,
                ],
                Call::ENTITY_TYPE => [
                    Table::ACTION_CREATE => Table::LEVEL_YES,
                    Table::ACTION_READ => Table::LEVEL_YES,
                ],
                User::ENTITY_TYPE => [
                    Table::ACTION_READ => Table::LEVEL_TEAM,
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
            Permission::ASSIGNMENT . 'Permission' => Table::LEVEL_ALL,
        ]);
    }

    private function configureApp(): void
    {
        $configWriter = $this->getInjectableFactory()->create(ConfigWriter::class);

        $configWriter->setMultiple([
            'siteUrl' => 'http://localhost'
        ]);

        $configWriter->save();
    }

    private function createTeam(): Team
    {
        $em = $this->getEntityManager();

        $team = $em->getRDBRepositoryByClass(Team::class)->getNew();
        $team->setName('Team 1');
        $em->saveEntity($team);

        return $team;
    }

    private function createTestUser(Team $team): User
    {
        $em = $this->getEntityManager();

        $testUser = $em->getRDBRepositoryByClass(User::class)->getNew()
            ->setUserName('test-0')
            ->setLastName('Test User 0')
            ->setTeams(LinkMultiple::create()->withAddedId($team->getId()));
        $em->saveEntity($testUser);

        return $testUser;
    }
}
