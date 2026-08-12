<?php
/**LICENSE**/

namespace integration\Espo\Modules\Mcp;

use Espo\Core\Acl\Table;
use Espo\Core\Api\RequestWrapper;
use Espo\Core\Authentication\Logins\ApiKey;
use Espo\Core\Binding\BindingContainerBuilder;
use Espo\Core\Field\EmailAddress;
use Espo\Core\Field\EmailAddressGroup;
use Espo\Core\Field\LinkMultiple;
use Espo\Core\Name\Field;
use Espo\Core\Utils\Json;
use Espo\Entities\Role;
use Espo\Entities\Team;
use Espo\Entities\User;
use Espo\Modules\Crm\Entities\Account;
use Espo\Modules\Crm\Entities\Lead;
use Espo\Modules\Crm\Entities\Opportunity;
use Espo\Modules\Mcp\Entities\Endpoint;
use Espo\Modules\Mcp\Entities\Feature;
use Espo\Modules\Mcp\Tools\Feature\Find\FindData;
use Espo\Modules\Mcp\Tools\Mcp\Api\PostEntry;
use Espo\Modules\Mcp\Tools\Mcp\JsonSchemaValidator\Validator;
use Espo\Modules\Mcp\Tools\Mcp\Method;
use Espo\Modules\Mcp\Tools\Mcp\Scope;
use Espo\Modules\Mcp\Tools\Mcp\Tool\ToolEnvelope;
use Espo\Modules\Mcp\Tools\Mcp\Tool\ToolProvider;
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
                        ],
                        primaryFilters: ['actual'],
                        boolFilters: ['onlyMy'],
                        filterFields: [
                            new FindData\Field('status'),
                        ],
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

        $this->assertEquals('Find.Lead', $tools[0]->name);
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

        // Call `Find.Lead`.

        $this->createRecords(
            team: $team,
        );

        $response = $apiAction->process(
            $this->createEntryRequest(
                method: Method::TOOLS_CALL,
                slug: 'test',
                id: 1,
                params: (object) [
                    'name' => 'Find.Lead',
                    'arguments' => (object) [
                        'primaryFilter' => 'actual',
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
        $this->assertEquals('test@test.com', $body->result->structuredContent->list[0]->emailAddress);
        $this->assertEquals(Lead::STATUS_NEW, $body->result->structuredContent->list[0]->status);

        $this->createJsonSchemaValidator()->assert(
            $this->getToolEnvelope($endpoint, 'Find.Lead')->tool->outputSchema,
            $body->result->structuredContent
        );

        //print_r($body);
    }

    private function createRecords(Team $team): void
    {
        $em = $this->getEntityManager();

        // Visible to the user.
        $em->saveEntity(
            $em->getRDBRepositoryByClass(Lead::class)->getNew()
                ->setTeams(LinkMultiple::create()->withAddedId($team->getId()))
                ->setLastName('Test 1')
                ->setEmailAddressGroup(EmailAddressGroup::create([EmailAddress::create('test@test.com')]))
                ->setStatus(Lead::STATUS_NEW)
        );

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
}
