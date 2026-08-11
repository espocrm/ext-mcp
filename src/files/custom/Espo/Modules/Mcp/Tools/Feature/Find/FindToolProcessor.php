<?php
/**LICENSE**/

namespace Espo\Modules\Mcp\Tools\Feature\Find;

use Espo\Core\Exceptions\BadRequest;
use Espo\Core\Exceptions\Forbidden;
use Espo\Core\Record\Collection;
use Espo\Core\Record\ServiceFactory;
use Espo\Core\Select\SearchParams;
use Espo\Entities\User;
use Espo\Modules\Mcp\Tools\Feature\Data;
use Espo\Modules\Mcp\Tools\JsonSchema\Type\ArrayType;
use Espo\Modules\Mcp\Tools\JsonSchema\Type\ObjectType;
use Espo\Modules\Mcp\Tools\Mcp\Exceptions\InternalError;
use Espo\Modules\Mcp\Tools\Mcp\Schema\General\RootSchema;
use Espo\Modules\Mcp\Tools\Mcp\Schema\Tool\CallToolRequestParams;
use Espo\Modules\Mcp\Tools\Mcp\Schema\Tool\CallToolResult;
use Espo\Modules\Mcp\Tools\Mcp\ToolsCall\ToolProcessor;
use Espo\ORM\Entity;
use Exception;
use RuntimeException;
use stdClass;

/**
 * @implements ToolProcessor<FindData>
 * @noinspection PhpUnused
 */
class FindToolProcessor implements ToolProcessor
{
    public function __construct(
        private ServiceFactory $serviceFactory,
        private EntityOutput $entityOutput,
        private User $user,
    ) {}

    public function process(CallToolRequestParams $params, Data $data, ?RootSchema $outputSchema): CallToolResult
    {
        if (!$outputSchema) {
            throw new InternalError("No output schema.");
        }

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
            'list' => $this->prepareList($recordCollection, $outputSchema),
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
     * @throws InternalError
     */
    private function prepareList(Collection $recordCollection, RootSchema $outputSchema): array
    {
        $attributes = $this->getOutputAttributes($outputSchema);

        $output = [];

        foreach ($recordCollection->getCollection() as $entity) {
            $item = $this->entityOutput->prepare($entity);

            foreach (get_object_vars($item) as $k => $v) {
                if (!array_key_exists($k, $attributes)) {
                    unset($item->$k);
                }
            }

            $output[] = $item;
        }

        return $output;
    }

    /**
     * @return string[]
     * @throws InternalError
     */
    private function getOutputAttributes(RootSchema $outputSchema): array
    {
        $outputSchema = $outputSchema->getSchema();

        if (!$outputSchema instanceof ObjectType) {
            throw new InternalError("Unexpected schema.");
        }

        $listSchema = $outputSchema->getProperties()['list'] ?? null;

        if (!$listSchema instanceof ArrayType) {
            throw new InternalError("Unexpected schema.");
        }

        $itemsSchema = $listSchema->getItems();

        if (!$itemsSchema instanceof ObjectType) {
            throw new InternalError("Unexpected schema.");
        }

        return array_keys($itemsSchema->getProperties());
    }
}
