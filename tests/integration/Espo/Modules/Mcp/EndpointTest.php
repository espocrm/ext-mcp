<?php
/**LICENSE**/

namespace integration\Espo\Modules\Mcp;

use Espo\Core\Acl\Table;
use Espo\Core\Authentication\Logins\ApiKey;
use Espo\Entities\Role;
use Espo\Entities\Team;
use Espo\Entities\User;
use Espo\Modules\Crm\Entities\Account;
use Espo\Modules\Crm\Entities\Lead;
use Espo\Modules\Crm\Entities\Opportunity;
use Espo\Modules\Mcp\Entities\McpEndpoint;
use Espo\Modules\Mcp\Tools\Mcp\Api\PostEntry;
use Espo\Modules\Mcp\Tools\Mcp\RecordItemAction;
use tests\integration\Core\BaseTestCase;

class EndpointTest extends BaseTestCase
{
    private const string TEST_API_KEY = 'test-key';

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
        ]);

        $endpoint = $em->getRDBRepositoryByClass(McpEndpoint::class)->getNew()
            ->setName('Test')
            ->setSlug('test')
            ->setActions([
                Account::ENTITY_TYPE . '.' . RecordItemAction::LIST,
                Lead::ENTITY_TYPE . '.' . RecordItemAction::LIST,
                Opportunity::ENTITY_TYPE . '.' . RecordItemAction::LIST,
            ]);
        $em->saveEntity($endpoint);

        $em->getRelation($endpoint, McpEndpoint::LINK_USERS)->relate($apiUser);

        $request = $this->createRequest(
            method: 'POST',
            headers: [
                'Content-Type' => 'application/json',
                'X-Api-Key' => self::TEST_API_KEY,
            ],
            body: '{}',
        );

        $this->authenticate(method: ApiKey::NAME, request: $request);

        $apiAction = $this->getInjectableFactory()->create(PostEntry::class);
    }
}
