<?php
/**LICENSE**/

namespace Espo\Modules\Mcp\Tools\Feature\RecordStream;

use Espo\Core\Exceptions\BadRequest;
use Espo\Core\Exceptions\Forbidden;
use Espo\Core\Exceptions\NotFound;
use Espo\Core\Name\Field;
use Espo\Core\Record\Collection;
use Espo\Core\Select\SearchParams;
use Espo\Core\Select\Where\Item as WhereItem;
use Espo\Entities\Note;
use Espo\Modules\Mcp\Tools\Feature\Data;
use Espo\Modules\Mcp\Tools\Feature\Find\EntityOutput;
use Espo\Modules\Mcp\Tools\Feature\Utils\ExceptionUtil;
use Espo\Modules\Mcp\Tools\JsonSchema\Type\ArrayType;
use Espo\Modules\Mcp\Tools\JsonSchema\Type\ObjectType;
use Espo\Modules\Mcp\Tools\Mcp\Exceptions\InternalError;
use Espo\Modules\Mcp\Tools\Mcp\Schema\General\RootSchema;
use Espo\Modules\Mcp\Tools\Mcp\Schema\Tool\CallToolRequestParams;
use Espo\Modules\Mcp\Tools\Mcp\Schema\Tool\CallToolResult;
use Espo\Modules\Mcp\Tools\Mcp\ToolsCall\ToolProcessor;
use Espo\Tools\Stream\RecordService;
use stdClass;

/**
 * @implements ToolProcessor<RecordStreamData>
 */
class RecordStreamToolProcessor implements ToolProcessor
{
    private const int DEFAULT_MAX_SIZE = 50;

    public function __construct(
        private RecordService $recordService,
        private EntityOutput $entityOutput,
        private ExceptionUtil $exceptionUtil,
    ) {}

    public function process(CallToolRequestParams $params, Data $data, ?RootSchema $outputSchema): CallToolResult
    {
        if (!$outputSchema) {
            throw new InternalError("No output schema.");
        }

        $id = $params->arguments->parentId ?? null;
        $entityType = $params->arguments->parentType ?? null;

        if (!is_string($id)) {
            throw new InternalError("No `id' provided.");
        }

        if (!is_string($entityType)) {
            throw new InternalError("No `entityType' provided.");
        }

        $searchParams = $this->fetchSearchParams($params);

        try {
            $recordCollection = $this->recordService->find($entityType, $id, $searchParams);
        } catch (BadRequest $e) {
            return $this->exceptionUtil->prepareWriteBadRequestResult($e);
        } catch (Forbidden) {
            return new CallToolResult(
                structuredContent: (object) [
                    'error' => (object) [
                        'code' => 403,
                        'message' => 'No stream access to the record.',
                    ],
                ],
                isError: true,
            );
        } catch (NotFound) {
            return new CallToolResult(
                structuredContent: (object) [
                    'error' => (object) [
                        'code' => 404,
                        'message' => 'Record not found.',
                    ],
                ],
                isError: true,
            );
        }

        $output = (object) [
            'records' => $this->prepareList($recordCollection, $outputSchema),
            'total' => $recordCollection->getTotal(),
        ];

        return new CallToolResult(
            structuredContent: $output,
        );
    }

    /**
     * @throws InternalError
     */
    private function fetchSearchParams(CallToolRequestParams $params): SearchParams
    {
        $maxSize = $params->arguments->maxSize ?? null;
        $offset = $params->arguments->offset ?? null;
        $after = $params->arguments->after ?? null;
        $types = $params->arguments->types ?? null;
        $textFilter = $params->arguments->textFilter ?? null;

        if ($maxSize !== null && !is_int($maxSize)) {
            throw new InternalError("Bad 'maxSize'.");
        }

        if ($offset !== null && !is_int($offset)) {
            throw new InternalError("Bad 'offset'.");
        }

        if ($after !== null && !is_string($after)) {
            throw new InternalError("Bad 'after'.");
        }

        if ($types !== null && !is_array($types)) {
            throw new InternalError("Bad 'types'.");
        }

        if ($types !== null) {
            foreach ($types as $it) {
                if (!is_string($it)) {
                    throw new InternalError("Bad 'types' item.");
                }
            }
        }

        $searchParams = SearchParams::create()
            ->withOffset($offset)
            ->withMaxSize($maxSize ?? self::DEFAULT_MAX_SIZE)
            ->withTextFilter($textFilter);

        if ($maxSize !== null) {
            $searchParams = $searchParams->withMaxSize(null);
        }

        if ($types) {
            $searchParams = $searchParams->withWhereAdded(
                WhereItem
                    ::createBuilder()
                    ->setAttribute(Note::FIELD_TYPE)
                    ->setType(WhereItem\Type::IN)
                    ->setValue($types)
                    ->build()
            );
        }

        if ($after) {
            $searchParams = $searchParams->withWhereAdded(
                WhereItem
                    ::createBuilder()
                    ->setAttribute(Field::CREATED_AT)
                    ->setType(WhereItem\Type::AFTER)
                    ->setValue($after)
                    ->build()
            );
        }

        return $searchParams;
    }

    /**
     * @param Collection<Note> $recordCollection
     * @return stdClass[]
     * @throws InternalError
     */
    private function prepareList(Collection $recordCollection, RootSchema $outputSchema): array
    {
        $recordSchema = $this->getRecordSchema($outputSchema);

        $output = [];

        foreach ($recordCollection->getCollection() as $entity) {
            $output[] = $this->entityOutput->prepare($entity, $recordSchema);
        }

        return $output;
    }

    /**
     * @throws InternalError
     */
    private function getRecordSchema(RootSchema $outputSchema): ObjectType
    {
        $outputSchema = $outputSchema->getSchema();

        if (!$outputSchema instanceof ObjectType) {
            throw new InternalError("Unexpected schema.");
        }

        $listSchema = $outputSchema->getProperties()['records'] ?? null;

        if (!$listSchema instanceof ArrayType) {
            throw new InternalError("Unexpected schema.");
        }

        $itemsSchema = $listSchema->getItems();

        if (!$itemsSchema instanceof ObjectType) {
            throw new InternalError("Unexpected schema.");
        }

        return $itemsSchema;
    }
}
