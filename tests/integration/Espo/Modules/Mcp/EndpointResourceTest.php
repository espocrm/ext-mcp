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
use Espo\Core\Name\Field;
use Espo\Core\ORM\Entity as CoreEntity;
use Espo\Core\Utils\Json;
use Espo\Entities\Attachment;
use Espo\Entities\Note;
use Espo\Entities\Role;
use Espo\Entities\User;
use Espo\Modules\Crm\Entities\Account;
use Espo\Modules\Crm\Entities\CaseObj;
use Espo\Modules\Crm\Entities\Contact;
use Espo\Modules\Crm\Entities\Document;
use Espo\Modules\Crm\Entities\KnowledgeBaseArticle;
use Espo\Modules\Crm\Entities\Opportunity;
use Espo\Modules\Mcp\Entities\Endpoint;
use Espo\Modules\Mcp\Entities\Feature;
use Espo\Modules\Mcp\Tools\Feature\Features\Find\FindData;
use Espo\Modules\Mcp\Tools\Feature\Features\Read\ReadData;
use Espo\Modules\Mcp\Tools\Feature\Features\RecordStream\RecordStreamData;
use Espo\Modules\Mcp\Tools\Mcp\Api\PostEntry;
use Espo\Modules\Mcp\Tools\Mcp\Exceptions\InvalidParamsError;
use Espo\Modules\Mcp\Tools\Mcp\JsonSchemaValidator\Validator;
use Espo\Modules\Mcp\Tools\Mcp\Method;
use Espo\Modules\Mcp\Tools\Mcp\Scope;
use Espo\Modules\Mcp\Tools\Mcp\Tool\ToolProvider;
use Espo\ORM\Entity;
use RuntimeException;
use stdClass;
use tests\integration\Core\BaseTestCase;

/**
 * Covers: `resources/list`, `resources/templates/list`, `resources/read`
 * with the Attachment resource template, and attachment resource links in the Read tool.
 */
class EndpointResourceTest extends BaseTestCase
{
    private const string TEST_API_KEY = 'test-key';
    private const string SLUG = 'test';

    private const int CODE_INVALID_PARAMS = -32602;
    private const int CODE_NO_ACCESS = -31003;
    private const int CODE_TOO_LARGE = -31122;

    private PostEntry $apiAction;

    /**
     * @noinspection PhpUnhandledExceptionInspection
     */
    public function testResources(): void
    {
        $this->configureApp();

        $apiUser = $this->createApiUser();
        $endpoint = $this->createEndpoint($apiUser);

        $this->createFeatures($endpoint);

        $this->authenticateApi();

        $this->processTestDiscover();
        $this->processTestResourcesList();
        $this->processTestResourceTemplatesList();
    }

    /**
     * Resource links for attachmentMultiple and file fields in the Read tool output.
     *
     * @noinspection PhpUnhandledExceptionInspection
     */
    public function testReadToolAttachmentLinks(): void
    {
        $this->configureApp();

        $apiUser = $this->createApiUser();
        $endpoint = $this->createEndpoint($apiUser);

        $this->createFeatures($endpoint);

        $em = $this->getEntityManager();

        // Attachment-Multiple.

        $case = $em->createEntity(CaseObj::ENTITY_TYPE, [
            Field::NAME => 'Test Case',
        ]);

        $caseAttachment1 = $this->createAttachment(
            contents: 'test-1',
            type: 'text/plain',
            name: 'a.txt',
            parent: $case,
            field: 'attachments',
        );

        $caseAttachment2 = $this->createAttachment(
            contents: 'test-2',
            type: 'image/png',
            name: 'b.png',
            parent: $case,
            field: 'attachments',
        );

        // File.

        [$document, $documentAttachment] = $this->createDocumentWithFile('c.pdf', 'application/pdf');

        $this->authenticateApi();

        // Read_Case. Attachment-Multiple.

        $body = $this->callTool(
            name: 'Read_' . CaseObj::ENTITY_TYPE,
            arguments: [
                'id' => $case->getId(),
                'selectFields' => ['attachments'],
            ],
        );

        $record = $body->result->structuredContent->record;

        $this->assertEqualsCanonicalizing(
            [$caseAttachment1->getId(), $caseAttachment2->getId()],
            $record->attachmentsIds,
        );

        $this->assertEquals('a.txt', $record->attachmentsNames->{$caseAttachment1->getId()});
        $this->assertEquals('b.png', $record->attachmentsNames->{$caseAttachment2->getId()});

        $this->processValidateJsonSchema($endpoint, 'Read_' . CaseObj::ENTITY_TYPE, $body->result->structuredContent);

        $links = $this->getAttachmentLinks($body->result->content);

        $this->assertCount(2, $links);

        $link1 = $links["espo://Attachment/{$caseAttachment1->getId()}"] ?? null;
        $link2 = $links["espo://Attachment/{$caseAttachment2->getId()}"] ?? null;

        $this->assertNotNull($link1);
        $this->assertNotNull($link2);

        $this->assertEquals('resource_link', $link1->type);
        $this->assertEquals('a.txt', $link1->name);
        $this->assertEquals('text/plain', $link1->mimeType ?? null);

        $this->assertEquals('b.png', $link2->name);
        $this->assertEquals('image/png', $link2->mimeType ?? null);

        // Read_Case. Attachments not selected, no attachment links.

        $body = $this->callTool(
            name: 'Read_' . CaseObj::ENTITY_TYPE,
            arguments: [
                'id' => $case->getId(),
                'selectFields' => [Field::NAME],
            ],
        );

        $this->assertCount(0, $this->getAttachmentLinks($body->result->content));

        // Read_Document. File.

        $body = $this->callTool(
            name: 'Read_' . Document::ENTITY_TYPE,
            arguments: [
                'id' => $document->getId(),
                'selectFields' => ['file'],
            ],
        );

        $record = $body->result->structuredContent->record;

        $this->assertEquals($documentAttachment->getId(), $record->fileId);
        $this->assertEquals('c.pdf', $record->fileName);

        $this->processValidateJsonSchema($endpoint, 'Read_' . Document::ENTITY_TYPE, $body->result->structuredContent);

        $links = $this->getAttachmentLinks($body->result->content);

        $this->assertCount(1, $links);

        $link = $links["espo://Attachment/{$documentAttachment->getId()}"] ?? null;

        $this->assertNotNull($link);
        $this->assertEquals('c.pdf', $link->name);
    }

    /**
     * Reading contents: text, binary, non-UTF-8 text, too large, not found, unknown URI.
     *
     * @noinspection PhpUnhandledExceptionInspection
     */
    public function testResourceReadContents(): void
    {
        $this->configureApp(attachmentMaxSize: 1);

        $apiUser = $this->createApiUser();
        $endpoint = $this->createEndpoint($apiUser);

        $this->createFeatures($endpoint);

        $em = $this->getEntityManager();

        $case = $em->createEntity(CaseObj::ENTITY_TYPE, [
            Field::NAME => 'Test Case',
        ]);

        $textContents = "Hello, світ.\n";
        $binaryContents = "\x89PNG\r\n\x1a\n\x00\x01\x02\xFF";
        $nonUtf8Contents = "\xCF\xF0\xE8\xE2\xB3\xF2"; // Windows-1251.
        $largeContents = str_repeat('a', 1024 * 1024 + 1);

        $textAttachment = $this->createAttachment($textContents, 'text/plain', 'text.txt', $case, 'attachments');
        $binaryAttachment = $this->createAttachment($binaryContents, 'image/png', 'image.png', $case, 'attachments');
        $nonUtf8Attachment = $this->createAttachment($nonUtf8Contents, 'text/plain', 'cp1251.txt', $case, 'attachments');
        $largeAttachment = $this->createAttachment($largeContents, 'text/plain', 'large.txt', $case, 'attachments');

        $this->authenticateApi();

        // Text.

        $uri = "espo://Attachment/{$textAttachment->getId()}";

        $body = $this->readResource($uri);

        $this->assertEquals('complete', $body->result?->resultType);
        $this->assertCount(1, $body->result->contents);

        $item = $body->result->contents[0];

        $this->assertEquals($uri, $item->uri);
        $this->assertEquals('text/plain', $item->mimeType);
        $this->assertEquals($textContents, $item->text);
        $this->assertObjectNotHasProperty('blob', $item);

        // Binary.

        $uri = "espo://Attachment/{$binaryAttachment->getId()}";

        $item = $this->readResource($uri)->result->contents[0];

        $this->assertEquals($uri, $item->uri);
        $this->assertEquals('image/png', $item->mimeType);
        $this->assertEquals(base64_encode($binaryContents), $item->blob);
        $this->assertObjectNotHasProperty('text', $item);

        // Non-UTF-8 text is returned as a blob.

        $item = $this->readResource("espo://Attachment/{$nonUtf8Attachment->getId()}")->result->contents[0];

        $this->assertEquals('text/plain', $item->mimeType);
        $this->assertEquals(base64_encode($nonUtf8Contents), $item->blob);
        $this->assertObjectNotHasProperty('text', $item);

        // Too large.

        $body = $this->readResource("espo://Attachment/{$largeAttachment->getId()}");

        $this->assertObjectNotHasProperty('result', $body);
        $this->assertEquals(self::CODE_TOO_LARGE, $body->error->code);

        // Not found.

        $body = $this->readResource("espo://Attachment/not-existing-id");

        $this->assertObjectNotHasProperty('result', $body);
        $this->assertEquals(self::CODE_INVALID_PARAMS, $body->error->code);

        // Unknown resource URI.

        $body = $this->readResource("espo://Unknown/{$textAttachment->getId()}");

        $this->assertObjectNotHasProperty('result', $body);
        $this->assertEquals(self::CODE_INVALID_PARAMS, $body->error->code);

        // No URI.

        $body = $this->readResource(null);

        $this->assertObjectNotHasProperty('result', $body);
        $this->assertObjectHasProperty('error', $body);
    }

    /**
     * Access: ACL and endpoint features.
     *
     * @noinspection PhpUnhandledExceptionInspection
     */
    public function testResourceReadAccess(): void
    {
        $this->configureApp();

        $apiUser = $this->createApiUser();
        $endpoint = $this->createEndpoint($apiUser);

        $this->createFeatures($endpoint);

        $em = $this->getEntityManager();

        $case = $em->createEntity(CaseObj::ENTITY_TYPE, [Field::NAME => 'Test Case']);
        $article = $em->createEntity(KnowledgeBaseArticle::ENTITY_TYPE, [Field::NAME => 'Test Article']);
        $account = $em->createEntity(Account::ENTITY_TYPE, [Field::NAME => 'Test Account']);
        $contact = $em->createEntity(Contact::ENTITY_TYPE, ['lastName' => 'Test Contact']);
        $opportunity = $em->createEntity(Opportunity::ENTITY_TYPE, [Field::NAME => 'Test Opportunity']);

        // Read feature with the field selected.
        $caseAttachment = $this->createAttachment('1', 'text/plain', '1.txt', $case, 'attachments');

        // Read feature for a File field (related, not parent).
        [, $documentAttachment] = $this->createDocumentWithFile('2.pdf', 'application/pdf');

        // Find feature with the field selected.
        $articleAttachment = $this->createAttachment('3', 'text/plain', '3.txt', $article, 'attachments');

        // Read feature exists, but the field is not selected.
        $accountAttachment = $this->createAttachment('4', 'text/plain', '4.txt', $account, 'description');

        // No feature for the entity type.
        $contactAttachment = $this->createAttachment('5', 'text/plain', '5.txt', $contact, 'description');

        // No ACL access to the parent record (read: own).
        $opportunityAttachment = $this->createAttachment('6', 'text/plain', '6.txt', $opportunity, 'description');

        // No parent, no field.
        $orphanAttachment = $this->createAttachment('7', 'text/plain', '7.txt');

        // Parent present, but no target field.
        $noFieldAttachment = $this->createAttachment('8', 'text/plain', '8.txt', $case);

        // Stream post on a record type covered by the RecordStream feature.
        $accountNote = $this->createPost($account);
        $accountNoteAttachment = $this->createAttachment('9', 'text/plain', '9.txt', $accountNote, 'attachments');

        // Stream post on a record type not covered by the RecordStream feature.
        $caseNote = $this->createPost($case);
        $caseNoteAttachment = $this->createAttachment('10', 'text/plain', '10.txt', $caseNote, 'attachments');

        // Stream post whose super-parent is covered by the RecordStream feature.
        $superParentNote = $this->createPost($case, $account);
        $superParentNoteAttachment = $this
            ->createAttachment('11', 'text/plain', '11.txt', $superParentNote, 'attachments');

        $this->authenticateApi();

        $this->assertReadable($caseAttachment, '1');
        $this->assertReadable($documentAttachment, 'test-file');
        $this->assertReadable($articleAttachment, '3');
        $this->assertReadable($accountNoteAttachment, '9');
        $this->assertReadable($superParentNoteAttachment, '11');

        $this->assertNoAccess($accountAttachment);
        $this->assertNoAccess($contactAttachment);
        $this->assertNoAccess($opportunityAttachment);
        $this->assertNoAccess($orphanAttachment);
        $this->assertNoAccess($noFieldAttachment);
        $this->assertNoAccess($caseNoteAttachment);
    }

    /**
     * An inactive feature does not grant access.
     *
     * @noinspection PhpUnhandledExceptionInspection
     */
    public function testResourceReadInactiveFeature(): void
    {
        $this->configureApp();

        $apiUser = $this->createApiUser();
        $endpoint = $this->createEndpoint($apiUser);

        $em = $this->getEntityManager();

        $em->saveEntity(
            $em->getRDBRepositoryByClass(Feature::class)->getNew()
                ->setType(ReadData::TYPE)
                ->setData(
                    new ReadData(
                        entityType: CaseObj::ENTITY_TYPE,
                        selectFields: [
                            new FindData\Field(Field::NAME),
                            new FindData\Field('attachments'),
                        ],
                    )
                )
                ->setInactive()
                ->setEndpoint($endpoint)
        );

        $case = $em->createEntity(CaseObj::ENTITY_TYPE, [Field::NAME => 'Test Case']);
        $attachment = $this->createAttachment('1', 'text/plain', '1.txt', $case, 'attachments');

        $this->authenticateApi();

        $this->assertNoAccess($attachment);
    }

    /**
     * @noinspection PhpUnhandledExceptionInspection
     */
    private function processTestDiscover(): void
    {
        $body = $this->call(Method::SERVER_DISCOVER);

        $this->assertEquals(
            (object) ['listChanged' => false],
            $body->result?->capabilities?->resources,
        );
    }

    /**
     * @noinspection PhpUnhandledExceptionInspection
     */
    private function processTestResourcesList(): void
    {
        $body = $this->call(Method::RESOURCES_LIST);

        $this->assertEquals('complete', $body->result?->resultType);
        $this->assertEquals('private', $body->result->cacheScope);
        $this->assertEquals([], $body->result->resources);
    }

    /**
     * @noinspection PhpUnhandledExceptionInspection
     */
    private function processTestResourceTemplatesList(): void
    {
        $body = $this->call(Method::RESOURCES_TEMPLATES_LIST);

        $this->assertEquals('complete', $body->result?->resultType);
        $this->assertEquals('private', $body->result->cacheScope);
        $this->assertIsArray($body->result->resourceTemplates);

        $template = array_find($body->result->resourceTemplates, fn ($it) => $it->name === 'Attachment');

        $this->assertNotNull($template);
        $this->assertEquals('espo://Attachment/{id}', $template->uriTemplate);
        $this->assertIsString($template->title ?? null);
        $this->assertIsString($template->description ?? null);
    }

    /**
     * @noinspection PhpUnhandledExceptionInspection
     */
    private function assertReadable(Attachment $attachment, string $expectedText): void
    {
        $uri = "espo://Attachment/{$attachment->getId()}";

        $body = $this->readResource($uri);

        $this->assertObjectHasProperty(
            'result',
            $body,
            "Attachment '{$attachment->getName()}' is expected to be readable. " .
            "Error: " . Json::encode($body->error ?? null)
        );

        $item = $body->result->contents[0];

        $this->assertEquals($uri, $item->uri);

        $actual = property_exists($item, 'text') ? $item->text : base64_decode($item->blob);

        $this->assertEquals($expectedText, $actual);
    }

    /**
     * @noinspection PhpUnhandledExceptionInspection
     */
    private function assertNoAccess(Attachment $attachment): void
    {
        $body = $this->readResource("espo://Attachment/{$attachment->getId()}");

        $this->assertObjectNotHasProperty(
            'result',
            $body,
            "Attachment '{$attachment->getName()}' is expected to be not readable."
        );

        $this->assertEquals(self::CODE_NO_ACCESS, $body->error->code);
    }

    /**
     * @param stdClass[] $content
     * @return array<string, stdClass>
     */
    private function getAttachmentLinks(array $content): array
    {
        $output = [];

        foreach ($content as $item) {
            if (!str_starts_with($item->uri ?? '', 'espo://Attachment/')) {
                continue;
            }

            $output[$item->uri] = $item;
        }

        return $output;
    }

    /**
     * @noinspection PhpUnhandledExceptionInspection
     */
    private function readResource(?string $uri): stdClass
    {
        return $this->call(
            method: Method::RESOURCES_READ,
            params: $uri !== null ? (object) ['uri' => $uri] : (object) [],
        );
    }

    /**
     * @param array<string, mixed> $arguments
     * @noinspection PhpUnhandledExceptionInspection
     */
    private function callTool(string $name, array $arguments): stdClass
    {
        $body = $this->call(
            method: Method::TOOLS_CALL,
            params: (object) [
                'name' => $name,
                'arguments' => (object) $arguments,
            ],
        );

        $this->assertObjectHasProperty('result', $body, "Tool '$name' error: " . Json::encode($body->error ?? null));
        $this->assertObjectHasProperty(
            'record',
            $body->result->structuredContent,
            "Tool '$name' error: " . Json::encode($body->result->structuredContent)
        );

        return $body;
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

    /**
     * @noinspection PhpUnhandledExceptionInspection
     */
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

    /**
     * @noinspection PhpUnhandledExceptionInspection
     */
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

    /**
     * @noinspection PhpUnhandledExceptionInspection
     */
    private function processValidateJsonSchema(Endpoint $endpoint, string $name, mixed $structuredContent): void
    {
        $toolProvider = $this->getInjectableFactory()->createWithBinding(
            ToolProvider::class,
            BindingContainerBuilder::create()
                ->bindInstance(Endpoint::class, $endpoint)
                ->build()
        );

        try {
            $this->getInjectableFactory()->create(Validator::class)->assert(
                $toolProvider->get($name)->tool->outputSchema,
                $structuredContent
            );
        } catch (InvalidParamsError $e) {
            throw new RuntimeException("Validation error. " . var_export($e->getData(), true));
        }
    }

    private function createAttachment(
        string $contents,
        string $type,
        string $name,
        ?Entity $parent = null,
        ?string $field = null,
    ): Attachment {

        if ($parent && !$parent instanceof CoreEntity) {
            throw new RuntimeException();
        }

        $em = $this->getEntityManager();

        $attachment = $em->getRDBRepositoryByClass(Attachment::class)->getNew()
            ->setName($name)
            ->setType($type)
            ->setRole(Attachment::ROLE_ATTACHMENT)
            ->setTargetField($field)
            ->setContents($contents);

        if ($parent) {
            $attachment->setParent($parent);
        }

        $em->saveEntity($attachment);

        return $attachment;
    }

    /**
     * @return array{Entity, Attachment}
     * @noinspection PhpSameParameterValueInspection
     */
    private function createDocumentWithFile(string $name, string $type): array
    {
        $em = $this->getEntityManager();

        $attachment = $em->getRDBRepositoryByClass(Attachment::class)->getNew()
            ->setName($name)
            ->setType($type)
            ->setRole(Attachment::ROLE_ATTACHMENT)
            ->setTargetField('file')
            ->setContents('test-file');

        $attachment->set('relatedType', Document::ENTITY_TYPE);

        $em->saveEntity($attachment);

        $document = $em->createEntity(Document::ENTITY_TYPE, [
            Field::NAME => 'Test Document',
            'fileId' => $attachment->getId(),
        ]);

        $attachment = $em->getRDBRepositoryByClass(Attachment::class)->getById($attachment->getId());

        assert($attachment !== null);

        $this->assertEquals($document->getId(), $attachment->getRelated()?->getId());

        return [$document, $attachment];
    }

    private function createPost(Entity $parent, ?Entity $superParent = null): Note
    {
        $em = $this->getEntityManager();

        $note = $em->getRDBRepositoryByClass(Note::class)->getNew()
            ->setParent($parent)
            ->setType(Note::TYPE_POST)
            ->setPost('Test.');

        if ($superParent) {
            $note->setSuperParent($superParent);
        }

        $em->saveEntity($note);

        return $note;
    }

    private function createFeatures(Endpoint $endpoint): void
    {
        $em = $this->getEntityManager();

        $em->saveEntity(
            $em->getRDBRepositoryByClass(Feature::class)->getNew()
                ->setType(ReadData::TYPE)
                ->setData(
                    new ReadData(
                        entityType: CaseObj::ENTITY_TYPE,
                        selectFields: [
                            new FindData\Field(Field::NAME),
                            new FindData\Field('attachments'),
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
                        entityType: Document::ENTITY_TYPE,
                        selectFields: [
                            new FindData\Field(Field::NAME),
                            new FindData\Field('file'),
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
                        entityType: KnowledgeBaseArticle::ENTITY_TYPE,
                        textFilter: false,
                        selectFields: [
                            new FindData\Field(Field::NAME),
                            new FindData\Field('attachments'),
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
                        entityType: Account::ENTITY_TYPE,
                        selectFields: [
                            new FindData\Field(Field::NAME),
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
                            new FindData\Field('description'),
                        ],
                    )
                )
                ->setEndpoint($endpoint)
        );

        $em->saveEntity(
            $em->getRDBRepositoryByClass(Feature::class)->getNew()
                ->setType(RecordStreamData::TYPE)
                ->setData(
                    new RecordStreamData(
                        entityTypes: [
                            Account::ENTITY_TYPE,
                        ],
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
                Account::ENTITY_TYPE => [
                    Table::ACTION_READ => Table::LEVEL_ALL,
                    Table::ACTION_STREAM => Table::LEVEL_ALL,
                ],
                CaseObj::ENTITY_TYPE => [
                    Table::ACTION_READ => Table::LEVEL_ALL,
                    Table::ACTION_STREAM => Table::LEVEL_ALL,
                ],
                Document::ENTITY_TYPE => [
                    Table::ACTION_READ => Table::LEVEL_ALL,
                ],
                KnowledgeBaseArticle::ENTITY_TYPE => [
                    Table::ACTION_READ => Table::LEVEL_ALL,
                ],
                Contact::ENTITY_TYPE => [
                    Table::ACTION_READ => Table::LEVEL_ALL,
                ],
                Opportunity::ENTITY_TYPE => [
                    Table::ACTION_READ => Table::LEVEL_OWN,
                ],
            ],
        ]);
    }

    private function configureApp(?int $attachmentMaxSize = null): void
    {
        $params = [
            'siteUrl' => 'http://localhost',
        ];

        if ($attachmentMaxSize !== null) {
            $params['mcpAttachmentResourceMaxSize'] = $attachmentMaxSize;
        }

        $this->setConfigParams($params);
    }
}
