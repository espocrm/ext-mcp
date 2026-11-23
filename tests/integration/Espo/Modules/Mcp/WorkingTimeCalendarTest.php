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

use Espo\Core\Api\RequestWrapper;
use Espo\Core\Authentication\Logins\ApiKey;
use Espo\Core\Binding\BindingContainerBuilder;
use Espo\Core\Name\Field;
use Espo\Core\Utils\Json;
use Espo\Entities\Role;
use Espo\Entities\User;
use Espo\Entities\WorkingTimeCalendar;
use Espo\Entities\WorkingTimeRange;
use Espo\Modules\Mcp\Entities\Endpoint;
use Espo\Modules\Mcp\Entities\Feature;
use Espo\Modules\Mcp\Tools\Feature\Features\Create\CreateData;
use Espo\Modules\Mcp\Tools\Feature\Features\Find\FindData;
use Espo\Modules\Mcp\Tools\Feature\Features\Read\ReadData;
use Espo\Modules\Mcp\Tools\Feature\Features\Update\UpdateData;
use Espo\Modules\Mcp\Tools\Mcp\Api\PostEntry;
use Espo\Modules\Mcp\Tools\Mcp\Exceptions\InvalidParamsError;
use Espo\Modules\Mcp\Tools\Mcp\JsonSchemaValidator\Validator;
use Espo\Modules\Mcp\Tools\Mcp\Method;
use Espo\Modules\Mcp\Tools\Mcp\Scope;
use Espo\Modules\Mcp\Tools\Mcp\Tool\ToolProvider;
use RuntimeException;
use stdClass;
use tests\integration\Core\BaseTestCase;

/**
 * Time range fields of WorkingTimeCalendar and WorkingTimeRange. Mandatory fields defined
 * in `mcpDefs`, with the schema provided by `WorkingTimeCalendar\TimeRangesSchemaProvider`.
 */
class WorkingTimeCalendarTest extends BaseTestCase
{
    private const string TEST_API_KEY = 'test-key';
    private const string SLUG = 'test';

    private const string CALENDAR_ENTITY_TYPE = WorkingTimeCalendar::ENTITY_TYPE;
    private const string RANGE_ENTITY_TYPE = WorkingTimeRange::ENTITY_TYPE;

    private const string PATTERN = '^([01][0-9]|2[0-3]):[0-5][0-9]$';

    private const array WEEKDAY_FIELDS = [
        'weekday0TimeRanges',
        'weekday1TimeRanges',
        'weekday2TimeRanges',
        'weekday3TimeRanges',
        'weekday4TimeRanges',
        'weekday5TimeRanges',
        'weekday6TimeRanges',
    ];

    private PostEntry $apiAction;
    private Endpoint $endpoint;

    public function testWriteSchema(): void
    {
        $this->prepare();

        $tools = $this->listTools();

        foreach (['Create_' . self::CALENDAR_ENTITY_TYPE, 'Update_' . self::CALENDAR_ENTITY_TYPE] as $toolName) {
            $properties = $tools[$toolName]->inputSchema->properties->record->properties;

            // Required field. Not nullable.
            $schema = $properties->timeRanges;

            $this->assertTimeRangesSchema($schema, self::PATTERN);
            $this->assertEquals(1, $schema->minItems);
            $this->assertEquals('Workday Schedule', $schema->title);
            $this->assertStringContainsString('HH:mm', $schema->description);

            // Not required fields. Nullable.
            foreach (self::WEEKDAY_FIELDS as $field) {
                $this->assertObjectHasProperty($field, $properties, "No '$field' in '$toolName'.");

                $schema = $properties->$field;

                $this->assertTimeRangesSchema($this->unwrapNullable($schema), self::PATTERN);
                $this->assertNotEmpty($schema->title);
            }
        }

        // Has a default value, so not required on create.
        $required = $tools['Create_' . self::CALENDAR_ENTITY_TYPE]->inputSchema->properties->record->required ?? [];

        $this->assertNotContains('timeRanges', $required);

        $rangeProperties = $tools['Create_' . self::RANGE_ENTITY_TYPE]->inputSchema->properties->record->properties;

        $this->assertTimeRangesSchema($this->unwrapNullable($rangeProperties->timeRanges), self::PATTERN);
    }

    public function testReadSchema(): void
    {
        $this->prepare();

        $tools = $this->listTools();

        $properties = $tools['Read_' . self::CALENDAR_ENTITY_TYPE]->outputSchema->properties->record->properties;

        $this->assertTimeRangesSchema($properties->timeRanges, self::PATTERN);

        foreach (self::WEEKDAY_FIELDS as $field) {
            $this->assertTimeRangesSchema($this->unwrapNullable($properties->$field), self::PATTERN);
        }

        $rangeProperties = $tools['Read_' . self::RANGE_ENTITY_TYPE]->outputSchema->properties->record->properties;

        $this->assertTimeRangesSchema($this->unwrapNullable($rangeProperties->timeRanges), self::PATTERN);
    }

    public function testCreateAndRead(): void
    {
        $this->prepare();

        $result = $this->callTool('Create_' . self::CALENDAR_ENTITY_TYPE, (object) [
            'record' => (object) [
                'name' => 'Test',
                'timeRanges' => [['08:30', '12:00'], ['13:00', '00:00']],
                'weekday1TimeRanges' => [['10:00', '14:00']],
                'weekday6TimeRanges' => null,
            ],
        ]);

        $id = $result->structuredContent->id ?? $result->structuredContent->record->id ?? null;

        $this->assertIsString($id, Json::encode($result));

        $calendar = $this->getEntityManager()->getRDBRepositoryByClass(WorkingTimeCalendar::class)->getById($id);

        $this->assertNotNull($calendar);
        $this->assertEquals([['08:30', '12:00'], ['13:00', '00:00']], $calendar->get('timeRanges'));
        $this->assertEquals([['10:00', '14:00']], $calendar->get('weekday1TimeRanges'));
        $this->assertNull($calendar->get('weekday6TimeRanges'));

        $record = $this->callTool('Read_' . self::CALENDAR_ENTITY_TYPE, (object) ['id' => $id])
            ->structuredContent->record;

        $this->assertEquals([['08:30', '12:00'], ['13:00', '00:00']], $record->timeRanges);
        $this->assertEquals([['10:00', '14:00']], $record->weekday1TimeRanges);
        $this->assertNull($record->weekday6TimeRanges);

        $this->assertOutputValid('Read_' . self::CALENDAR_ENTITY_TYPE, (object) ['record' => $record]);
    }

    public function testUpdate(): void
    {
        $calendar = $this->getEntityManager()->createEntity(self::CALENDAR_ENTITY_TYPE, [
            Field::NAME => 'Test',
            'timeRanges' => [['09:00', '17:00']],
            'weekday2TimeRanges' => [['09:00', '12:00']],
        ]);

        $this->prepare();

        $this->callTool('Update_' . self::CALENDAR_ENTITY_TYPE, (object) [
            'id' => $calendar->getId(),
            'record' => (object) [
                'timeRanges' => [['07:00', '11:00'], ['12:00', '16:00']],
                'weekday2TimeRanges' => null,
                'weekday3TimeRanges' => [['00:00', '00:00']],
            ],
        ]);

        $this->getEntityManager()->refreshEntity($calendar);

        $this->assertEquals([['07:00', '11:00'], ['12:00', '16:00']], $calendar->get('timeRanges'));
        $this->assertNull($calendar->get('weekday2TimeRanges'));
        $this->assertEquals([['00:00', '00:00']], $calendar->get('weekday3TimeRanges'));
    }

    public function testWorkingTimeRangeCreateAndRead(): void
    {
        $calendar = $this->getEntityManager()->createEntity(self::CALENDAR_ENTITY_TYPE, [
            Field::NAME => 'Test',
        ]);

        $this->prepare();

        $result = $this->callTool('Create_' . self::RANGE_ENTITY_TYPE, (object) [
            'record' => (object) [
                'name' => 'Short day',
                'type' => 'Working',
                'dateStart' => '2026-12-24',
                'dateEnd' => '2026-12-24',
                'calendarsIds' => [$calendar->getId()],
                'timeRanges' => [['09:00', '13:00']],
            ],
        ]);

        $id = $result->structuredContent->id ?? $result->structuredContent->record->id ?? null;

        $this->assertIsString($id, Json::encode($result));

        $range = $this->getEntityManager()->getRDBRepositoryByClass(WorkingTimeRange::class)->getById($id);

        $this->assertNotNull($range);
        $this->assertEquals([['09:00', '13:00']], $range->get('timeRanges'));

        $record = $this->callTool('Read_' . self::RANGE_ENTITY_TYPE, (object) ['id' => $id])
            ->structuredContent->record;

        $this->assertEquals([['09:00', '13:00']], $record->timeRanges);

        $this->assertOutputValid('Read_' . self::RANGE_ENTITY_TYPE, (object) ['record' => $record]);
    }

    public function testWorkingTimeRangeNullTimeRanges(): void
    {
        $calendar = $this->getEntityManager()->createEntity(self::CALENDAR_ENTITY_TYPE, [
            Field::NAME => 'Test',
        ]);

        $this->prepare();

        $result = $this->callTool('Create_' . self::RANGE_ENTITY_TYPE, (object) [
            'record' => (object) [
                'name' => 'Holiday',
                'type' => 'Non-working',
                'dateStart' => '2026-12-25',
                'dateEnd' => '2026-12-26',
                'calendarsIds' => [$calendar->getId()],
                'timeRanges' => null,
            ],
        ]);

        $id = $result->structuredContent->id ?? $result->structuredContent->record->id ?? null;

        $this->assertIsString($id, Json::encode($result));

        $record = $this->callTool('Read_' . self::RANGE_ENTITY_TYPE, (object) ['id' => $id])
            ->structuredContent->record;

        $this->assertNull($record->timeRanges);

        $this->assertOutputValid('Read_' . self::RANGE_ENTITY_TYPE, (object) ['record' => $record]);
    }

    /**
     * @noinspection PhpSameParameterValueInspection
     */
    private function assertTimeRangesSchema(stdClass $schema, string $pattern): void
    {
        $this->assertEquals('array', $schema->type);

        $range = $schema->items;

        $this->assertEquals('array', $range->type);
        $this->assertEquals(2, $range->minItems);
        $this->assertEquals(2, $range->maxItems);

        $this->assertEquals('string', $range->items->type);
        $this->assertEquals($pattern, $range->items->pattern);
    }

    /**
     * Returns the non-null branch of a nullable `anyOf` schema.
     */
    private function unwrapNullable(stdClass $schema): stdClass
    {
        $this->assertObjectHasProperty('anyOf', $schema, "Expected a nullable schema.");

        $types = array_map(fn ($it) => $it->type ?? null, $schema->anyOf);

        $this->assertContains('null', $types);

        foreach ($schema->anyOf as $item) {
            if (($item->type ?? null) !== 'null') {
                return $item;
            }
        }

        throw new RuntimeException("No non-null schema.");
    }

    /**
     * @noinspection PhpUnhandledExceptionInspection
     */
    private function assertOutputValid(string $toolName, stdClass $structuredContent): void
    {
        $tool = $this->getInjectableFactory()
            ->createWithBinding(
                ToolProvider::class,
                BindingContainerBuilder::create()
                    ->bindInstance(Endpoint::class, $this->endpoint)
                    ->build()
            )
            ->get($toolName)
            ->tool;

        try {
            $this->getInjectableFactory()->create(Validator::class)->assert($tool->outputSchema, $structuredContent);
        } catch (InvalidParamsError $e) {
            $this->fail("Output schema validation failure. " . var_export($e->getData(), true));
        }
    }

    private function prepare(): void
    {
        $this->setConfigParams([
            'siteUrl' => 'http://localhost',
        ]);

        $apiUser = $this->createApiUser();
        $this->endpoint = $this->createEndpoint($apiUser);

        $this->createFeatures($this->endpoint);

        $this->authenticateApi();
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

    private function callTool(string $name, stdClass $arguments): stdClass
    {
        $body = $this->call(Method::TOOLS_CALL, (object) [
            'name' => $name,
            'arguments' => $arguments,
        ]);

        $this->assertObjectHasProperty('result', $body, "Error: " . Json::encode($body->error ?? null));
        $this->assertFalse($body->result->isError ?? false, Json::encode($body->result));

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

    /**
     * Only `description` is configured. The time range fields are mandatory, defined in `mcpDefs`.
     */
    private function createFeatures(Endpoint $endpoint): void
    {
        foreach ([self::CALENDAR_ENTITY_TYPE, self::RANGE_ENTITY_TYPE] as $entityType) {
            $fields = [new FindData\Field('description')];

            $this->createFeature($endpoint, ReadData::TYPE, new ReadData(
                entityType: $entityType,
                selectFields: $fields,
            ));

            $this->createFeature($endpoint, CreateData::TYPE, new CreateData(
                entityType: $entityType,
                writeFields: $fields,
            ));

            $this->createFeature($endpoint, UpdateData::TYPE, new UpdateData(
                entityType: $entityType,
                writeFields: $fields,
            ));
        }
    }

    private function createFeature(
        Endpoint $endpoint,
        string $type,
        ReadData|CreateData|UpdateData $data,
    ): void {

        $em = $this->getEntityManager();

        $em->saveEntity(
            $em->getRDBRepositoryByClass(Feature::class)->getNew()
                ->setType($type)
                ->setData($data)
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
                self::CALENDAR_ENTITY_TYPE => true,
            ],
        ]);
    }
}
