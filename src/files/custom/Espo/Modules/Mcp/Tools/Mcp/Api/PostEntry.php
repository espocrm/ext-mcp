<?php
/**LICENSE**/

namespace Espo\Modules\Mcp\Tools\Mcp\Api;

use Espo\Core\Api\Action;
use Espo\Core\Api\Request;
use Espo\Core\Api\Response;
use Espo\Core\Api\ResponseWrapper;
use Espo\Core\Exceptions\BadRequest;
use Espo\Core\Exceptions\Forbidden;
use Espo\Entities\User;
use Espo\Modules\Mcp\Tools\Mcp\EntryService;

/**
 * @noinspection PhpUnused
 */
class PostEntry implements Action
{
    public function __construct(
        private User $user,
        private EntryService $service,
    ) {}

    public function process(Request $request): Response
    {
        if (!$this->user->isApi()) {
            throw new Forbidden("Only API users allowed.");
        }

        $slug = $request->getRouteParam('slug') ?? throw new BadRequest();

        $response = $this->service->process($slug, $request);

        return new ResponseWrapper($response);
    }
}
