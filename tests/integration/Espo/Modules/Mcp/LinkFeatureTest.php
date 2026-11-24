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
use Espo\Core\Binding\BindingContainerBuilder;
use Espo\Core\Utils\Json;
use Espo\Entities\Role;
use Espo\Entities\User;
use Espo\Modules\Crm\Entities\Account;
use Espo\Modules\Crm\Entities\Contact;
use Espo\Modules\Crm\Entities\Opportunity;
use Espo\Modules\Mcp\Entities\Endpoint;
use Espo\Modules\Mcp\Entities\Feature;
use Espo\Modules\Mcp\Tools\Feature\Features\Link\LinkData;
use Espo\Modules\Mcp\Tools\Mcp\Api\PostEntry;
use Espo\Modules\Mcp\Tools\Mcp\Exceptions\InvalidParamsError;
use Espo\Modules\Mcp\Tools\Mcp\JsonSchemaValidator\Validator;
use Espo\Modules\Mcp\Tools\Mcp\Method;
use Espo\Modules\Mcp\Tools\Mcp\Scope;
use Espo\Modules\Mcp\Tools\Mcp\Tool\ToolProvider;
use RuntimeException;
use stdClass;
use tests\integration\Core\BaseTestCase;

class LinkFeatureTest extends BaseTestCase
{
    private const string TEST_API_KEY = 'test-key';
    private const string SLUG = 'test';

    private const string TOOL_NAME = 'Link_' . Account::ENTITY_TYPE;

    private const string CONTACTS_DESCRIPTION = 'Contacts of the account.';

    private PostEntry $apiAction;
    private Endpoint $endpoint;

    public function testToolsList(): void
    {
        $this->prepare();

        $tools = $this->listTools();

        $this->assertArrayHasKey(self::TOOL_NAME, $tools);

        $tool = $tools[self::TOOL_NAME];

        $this->assertEquals(['action', 'id', 'link', 'foreignId'], $tool->inputSchema->required);
        $this->assertTrue($tool->annotations->idempotentHint);

        $items = $tool->inputSchema->properties->link->anyOf;

        // No access to Opportunity.
        $this->assertCount(1, $items);
        $this->assertEquals('contacts', $items[0]->const);
        $this->assertStringContainsString('`Contact`', $items[0]->description);
        $this->assertStringContainsString(self::CONTACTS_DESCRIPTION, $items[0]->description);
    }

    public function testNoEditAccess(): void
    {
        $this->prepare([
            Account::ENTITY_TYPE => [
                Table::ACTION_READ => Table::LEVEL_ALL,
                Table::ACTION_EDIT => Table::LEVEL_NO,
            ],
            Contact::ENTITY_TYPE => [
                Table::ACTION_READ => Table::LEVEL_ALL,
                Table::ACTION_EDIT => Table::LEVEL_ALL,
            ],
        ]);

        $this->assertArrayNotHasKey(self::TOOL_NAME, $this->listTools());
    }

    public function testLinkRequiredAccess(): void
    {
        $metadata = $this->getMetadata();

        $metadata->set('recordDefs', Account::ENTITY_TYPE, [
            'relationships' => [
                'contacts' => [
                    'linkRequiredAccess' => Table::ACTION_READ,
                ],
            ],
        ]);

        $metadata->save();

        $this->prepare([
            Account::ENTITY_TYPE => [
                Table::ACTION_READ => Table::LEVEL_ALL,
                Table::ACTION_EDIT => Table::LEVEL_NO,
            ],
            Contact::ENTITY_TYPE => [
                Table::ACTION_READ => Table::LEVEL_ALL,
                Table::ACTION_EDIT => Table::LEVEL_ALL,
            ],
        ]);

        $tools = $this->listTools();

        $this->assertArrayHasKey(self::TOOL_NAME, $tools);

        $items = $tools[self::TOOL_NAME]->inputSchema->properties->link->anyOf;

        $this->assertCount(1, $items);
        $this->assertEquals('contacts', $items[0]->const);

        $em = $this->getEntityManager();

        $account = $em->createEntity(Account::ENTITY_TYPE, ['name' => 'Account 1']);
        $contact = $em->createEntity(Contact::ENTITY_TYPE, ['lastName' => 'Contact 1']);

        $result = $this->callTool(self::TOOL_NAME, (object) [
            'action' => 'link',
            'id' => $account->getId(),
            'link' => 'contacts',
            'foreignId' => $contact->getId(),
        ]);

        $this->assertFalse($result->isError ?? false, Json::encode($result));

        $this->assertTrue($em->getRelation($account, 'contacts')->isRelated($contact));
    }

    public function testLink(): void
    {
        $this->prepare();

        $em = $this->getEntityManager();

        $account = $em->createEntity(Account::ENTITY_TYPE, ['name' => 'Account 1']);
        $contact = $em->createEntity(Contact::ENTITY_TYPE, ['lastName' => 'Contact 1']);

        $result = $this->callTool(self::TOOL_NAME, (object) [
            'action' => 'link',
            'id' => $account->getId(),
            'link' => 'contacts',
            'foreignId' => $contact->getId(),
        ]);

        $this->assertFalse($result->isError ?? false, Json::encode($result));
        $this->assertEquals($account->getId(), $result->structuredContent->record->id);
        $this->assertOutputValid($result->structuredContent);

        $this->assertTrue($em->getRelation($account, 'contacts')->isRelated($contact));

        // Idempotent.
        $result = $this->callTool(self::TOOL_NAME, (object) [
            'action' => 'link',
            'id' => $account->getId(),
            'link' => 'contacts',
            'foreignId' => $contact->getId(),
        ]);

        $this->assertFalse($result->isError ?? false, Json::encode($result));
    }

    public function testUnlink(): void
    {
        $this->prepare();

        $em = $this->getEntityManager();

        $account = $em->createEntity(Account::ENTITY_TYPE, ['name' => 'Account 1']);
        $contact = $em->createEntity(Contact::ENTITY_TYPE, ['lastName' => 'Contact 1']);

        $em->getRelation($account, 'contacts')->relate($contact);

        $result = $this->callTool(self::TOOL_NAME, (object) [
            'action' => 'unlink',
            'id' => $account->getId(),
            'link' => 'contacts',
            'foreignId' => $contact->getId(),
        ]);

        $this->assertFalse($result->isError ?? false, Json::encode($result));
        $this->assertEquals($account->getId(), $result->structuredContent->record->id);
        $this->assertOutputValid($result->structuredContent);

        $this->assertFalse($em->getRelation($account, 'contacts')->isRelated($contact));

        // Idempotent.
        $result = $this->callTool(self::TOOL_NAME, (object) [
            'action' => 'unlink',
            'id' => $account->getId(),
            'link' => 'contacts',
            'foreignId' => $contact->getId(),
        ]);

        $this->assertFalse($result->isError ?? false, Json::encode($result));
    }

    public function testBadAction(): void
    {
        $this->prepare();

        $em = $this->getEntityManager();

        $account = $em->createEntity(Account::ENTITY_TYPE, ['name' => 'Account 1']);
        $contact = $em->createEntity(Contact::ENTITY_TYPE, ['lastName' => 'Contact 1']);

        $result = $this->callTool(self::TOOL_NAME, (object) [
            'action' => 'relate',
            'id' => $account->getId(),
            'link' => 'contacts',
            'foreignId' => $contact->getId(),
        ]);

        $this->assertTrue($result->isError);

        $this->assertFalse($em->getRelation($account, 'contacts')->isRelated($contact));
    }

    public function testNotFound(): void
    {
        $this->prepare();

        $em = $this->getEntityManager();

        $account = $em->createEntity(Account::ENTITY_TYPE, ['name' => 'Account 1']);
        $contact = $em->createEntity(Contact::ENTITY_TYPE, ['lastName' => 'Contact 1']);

        $result = $this->callTool(self::TOOL_NAME, (object) [
            'action' => 'link',
            'id' => $account->getId(),
            'link' => 'contacts',
            'foreignId' => 'not-existing-id',
        ]);

        $this->assertTrue($result->isError);
        $this->assertEquals(404, $result->structuredContent->error->code);
        $this->assertOutputValid($result->structuredContent);

        $result = $this->callTool(self::TOOL_NAME, (object) [
            'action' => 'link',
            'id' => 'not-existing-id',
            'link' => 'contacts',
            'foreignId' => $contact->getId(),
        ]);

        $this->assertTrue($result->isError);
        $this->assertEquals(404, $result->structuredContent->error->code);
    }

    public function testNotAllowedLink(): void
    {
        $this->prepare();

        $em = $this->getEntityManager();

        $account = $em->createEntity(Account::ENTITY_TYPE, ['name' => 'Account 1']);
        $opportunity = $em->createEntity(Opportunity::ENTITY_TYPE, ['name' => 'Opportunity 1']);

        // In the feature, but no access to Opportunity. Not in the input schema.
        $body = $this->call(Method::TOOLS_CALL, (object) [
            'name' => self::TOOL_NAME,
            'arguments' => (object) [
                'action' => 'link',
                'id' => $account->getId(),
                'link' => 'opportunities',
                'foreignId' => $opportunity->getId(),
            ],
        ]);

        $this->assertTrue($body->result?->isError, "Is error tool response.");

        $this->assertFalse($em->getRelation($account, 'opportunities')->isRelated($opportunity));
    }

    /**
     * @noinspection PhpUnhandledExceptionInspection
     */
    private function assertOutputValid(stdClass $structuredContent): void
    {
        $tool = $this->getInjectableFactory()
            ->createWithBinding(
                ToolProvider::class,
                BindingContainerBuilder::create()
                    ->bindInstance(Endpoint::class, $this->endpoint)
                    ->build()
            )
            ->get(self::TOOL_NAME)
            ->tool;

        try {
            $this->getInjectableFactory()->create(Validator::class)->assert($tool->outputSchema, $structuredContent);
        } catch (InvalidParamsError $e) {
            $this->fail("Output schema validation failure. " . var_export($e->getData(), true));
        }
    }

    /**
     * @param ?array<string, mixed> $scopeData
     */
    private function prepare(?array $scopeData = null): void
    {
        $this->setConfigParams([
            'siteUrl' => 'http://localhost',
        ]);

        $scopeData ??= [
            Account::ENTITY_TYPE => [
                Table::ACTION_READ => Table::LEVEL_ALL,
                Table::ACTION_EDIT => Table::LEVEL_ALL,
            ],
            Contact::ENTITY_TYPE => [
                Table::ACTION_READ => Table::LEVEL_ALL,
                Table::ACTION_EDIT => Table::LEVEL_ALL,
            ],
            Opportunity::ENTITY_TYPE => false,
        ];

        $apiUser = $this->createApiUser($scopeData);
        $this->endpoint = $this->createEndpoint($apiUser);

        $this->createFeature($this->endpoint);

        $this->authenticateApi();
    }

    private function createFeature(Endpoint $endpoint): void
    {
        $em = $this->getEntityManager();

        $em->saveEntity(
            $em->getRDBRepositoryByClass(Feature::class)->getNew()
                ->setType(LinkData::TYPE)
                ->setData(
                    new LinkData(
                        entityType: Account::ENTITY_TYPE,
                        links: [
                            new LinkData\Link('contacts', self::CONTACTS_DESCRIPTION),
                            new LinkData\Link('opportunities'),
                        ],
                    )
                )
                ->setEndpoint($endpoint)
        );
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
     * @noinspection PhpSameParameterValueInspection
     */
    private function callTool(string $name, stdClass $arguments): stdClass
    {
        $body = $this->call(Method::TOOLS_CALL, (object) [
            'name' => $name,
            'arguments' => $arguments,
        ]);

        $this->assertObjectHasProperty('result', $body, "Error: " . Json::encode($body->error ?? null));

        return $body->result;
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

    /**
     * @param array<string, mixed> $scopeData
     */
    private function createApiUser(array $scopeData): User
    {
        return $this->createUser([
            User::FIELD_USER_NAME => 'api',
            User::FIELD_TYPE => User::TYPE_API,
            User::FIELD_AUTH_METHOD => User::AUTH_METHOD_API_KEY,
            User::FIELD_API_KEY => self::TEST_API_KEY,
        ], [
            Role::FIELD_DATA => [
                Scope::MCP => true,
                ...$scopeData,
            ],
        ]);
    }
}
