<?php
/**LICENSE**/

namespace Espo\Modules\Mcp\Tools\Feature\Find;

use Espo\Core\Exceptions\BadRequest;
use Espo\Core\Exceptions\Forbidden;
use Espo\Core\Record\Collection;
use Espo\Core\Record\ServiceFactory;
use Espo\Core\Select\SearchParams;
use Espo\Entities\User;
use Espo\Modules\Mcp\Entities\Feature;
use Espo\Modules\Mcp\Tools\Mcp\Exceptions\InternalError;
use Espo\Modules\Mcp\Tools\Mcp\Schema\Tool\CallToolRequestParams;
use Espo\Modules\Mcp\Tools\Mcp\Schema\Tool\CallToolResult;
use Espo\Modules\Mcp\Tools\Mcp\ToolsCall\ToolProcessor;
use Espo\ORM\Entity;
use Exception;
use RuntimeException;
use stdClass;

/**
 * @noinspection PhpUnused
 * @todo Filter output.
 */
class FindToolProcessor implements ToolProcessor
{
    public function __construct(
        private ServiceFactory $serviceFactory,
        private EntityOutput $entityOutput,
        private User $user,
    ) {}

    public function process(CallToolRequestParams $params, Feature $feature): CallToolResult
    {
        $entityType = $this->fetchEntityType($params);
        $searchParams = $this->fetchSearchParams($params);

        try {
            $service = $this->serviceFactory->createForUser($entityType, $this->user);
        } catch (Exception $e) {
            throw new InternalError("Could not create record service for `$entityType`.", previous: $e);
        }

        try {
            $recordCollection = $service->find($searchParams);
        } catch (BadRequest|Forbidden $e) {
            throw new InternalError("Error while performing 'find' action.", previous: $e);
        }

        $output = (object) [
            'list' => $this->prepareList($recordCollection),
            'total' => $recordCollection->getTotal(),
        ];

        return new CallToolResult(
            structuredContent: $output,
        );
    }

    private function fetchEntityType(CallToolRequestParams $params): string
    {
        $name = $params->name;

        if (!str_contains($name, '.')) {
            throw new RuntimeException("Bad tool name.");
        }

        [, $entityType] = explode('.', $name);

        return $entityType;
    }

    private function fetchSearchParams(CallToolRequestParams $params): SearchParams
    {
        $searchParams = SearchParams::fromRaw($params->arguments ?? (object) []);

        if ($searchParams->getMaxSize() === null) {
            $searchParams = $searchParams->withMaxSize(FindToolDefinitionProvider::MAX_SIZE_LIMIT);
        }

        if ($searchParams->getMaxSize() > FindToolDefinitionProvider::MAX_SIZE_LIMIT) {
            throw new RuntimeException("Max size exceeds limit.");
        }

        return $searchParams;
    }

    /**
     * @param Collection<Entity> $recordCollection
     * @return stdClass[]
     */
    private function prepareList(Collection $recordCollection): array
    {
        $output = [];

        foreach ($recordCollection->getCollection() as $entity) {
            $output[] = $this->entityOutput->prepare($entity);
        }

        return $output;
    }
}
