<?php
/**LICENSE**/

namespace Espo\Modules\Mcp\Tools\Feature\Features\RecordStream;

use Espo\Core\Acl;
use Espo\Core\Utils\Language;
use Espo\Entities\Note;
use Espo\Modules\Mcp\Tools\Feature\Data;
use Espo\Modules\Mcp\Tools\Feature\Exceptions\NoUserAccess;
use Espo\Modules\Mcp\Tools\Feature\ToolDefinitionProvider;
use Espo\Modules\Mcp\Tools\JsonSchema\ConstSchema;
use Espo\Modules\Mcp\Tools\JsonSchema\GroupSchema;
use Espo\Modules\Mcp\Tools\JsonSchema\Schema;
use Espo\Modules\Mcp\Tools\JsonSchema\StringFormat;
use Espo\Modules\Mcp\Tools\JsonSchema\Type\ArrayType;
use Espo\Modules\Mcp\Tools\JsonSchema\Type\BooleanType;
use Espo\Modules\Mcp\Tools\JsonSchema\Type\IntegerType;
use Espo\Modules\Mcp\Tools\JsonSchema\Type\ObjectType;
use Espo\Modules\Mcp\Tools\JsonSchema\Type\StringType;
use Espo\Modules\Mcp\Tools\Mcp\Schema\General\RootObjectSchema;
use Espo\Modules\Mcp\Tools\Mcp\Schema\General\RootSchema;
use Espo\Modules\Mcp\Tools\Mcp\Schema\Tool\Tool;
use Espo\Modules\Mcp\Tools\Mcp\Schema\Tool\ToolAnnotations;
use Espo\Modules\Mcp\Tools\Schema\Field\Util;
use Espo\ORM\Name\Attribute;

/**
 * @implements ToolDefinitionProvider<RecordStreamData>
 */
class RecordStreamToolDefinitionProvider implements ToolDefinitionProvider
{
    private const int MAX_SIZE_LIMIT = 100;

    private const string DESCRIPTION =
        "Returns activity stream entries (such as post and updates) for a specific record. " .
        "Entries are sorted by creation date in reverse order.";

    private const string MAX_SIZE_DESCRIPTION = 'Maximum number of records to fetch.';

    private const string OFFSET_DESCRIPTION = 'Offset for pagination.';

    private const string TEXT_FILTER_DESCRIPTION = 'Text filter.';

    private const string AFTER_DESCRIPTION = "To return only records created after the specified timestamp.";

    private const string PARENT_TYPE_DESCRIPTION = "Parent entity type.";

    private const string PARENT_ID_DESCRIPTION = "Parent entity ID. " .
        "IDs can be looked up with tools `Find_{EntityType}`.";

    private const string TYPES_DESCRIPTION = "What entry types to return.";

    private const string TITLE_TYPE_POST = 'Post';
    private const string DESC_TYPE_POST = 'Stream posts, usually created by users.';

    private const string TITLE_TYPE_UPDATE = 'Update';
    private const string DESC_TYPE_UPDATE = 'Record updates. Such as status changes and audited filed changes.';

    private const string TITLE_TYPE_CREATE = 'Create';
    private const string DESC_TYPE_CREATE = 'Record creation. Also includes information about initial assignment.';

    private const string TITLE_TYPE_ASSIGN = 'Assign';
    private const string DESC_TYPE_ASSIGN = 'Record assignment.';

    private const string TITLE_TYPE_EMAIL_SENT = 'Email Sent';
    private const string DESC_TYPE_EMAIL_SENT = 'Outbound emails record to the record.';

    private const string TITLE_TYPE_EMAIL_RECEIVED = 'Email Received';
    private const string DESC_TYPE_EMAIL_RECEIVED = 'Inbound emails record to the record.';

    public function __construct(
        private Language $defaultLanguage,
        private Acl $acl,
    ) {}

    public function get(Data $data): Tool
    {
        if ($this->getEntityTypes($data) === []) {
            throw new NoUserAccess("No stream access to any entity type.");
        }

        return new Tool(
            name: RecordStreamData::TYPE,
            inputSchema: new RootObjectSchema($this->prepareInputSchema($data)),
            outputSchema: new RootSchema($this->prepareOutputSchema()),
            description: self::DESCRIPTION,
            annotations: new ToolAnnotations(
                readOnlyHint: true,
                destructiveHint: false,
                openWorldHint: false,
            ),
        );
    }

    private function prepareInputSchema(RecordStreamData $data): ObjectType
    {
        $properties = [
            'parentType' => GroupSchema::createAnyOf(
                schemas: array_map(function ($entityType) {
                    return new ConstSchema(
                        value: $entityType,
                        title: $this->defaultLanguage->translateLabel($entityType, 'scopeNames')
                    );
                }, $this->getEntityTypes($data)),
                description: self::PARENT_TYPE_DESCRIPTION,
            ),
            'parentId' => new StringType(
                description: self::PARENT_ID_DESCRIPTION,
            ),
            'types' => new ArrayType(
                items: GroupSchema::createAnyOf(
                    schemas: $this->getTypeSchemas(),
                    description: self::TYPES_DESCRIPTION,
                ),
                minItems: 1,
                description: self::TYPES_DESCRIPTION,
            ),
            'maxSize' => new IntegerType(
                minimum: 1,
                maximum: self::MAX_SIZE_LIMIT,
                description: self::MAX_SIZE_DESCRIPTION,
            ),
            'offset' => new IntegerType(
                minimum: 0,
                description: self::OFFSET_DESCRIPTION,
            ),
            'textFilter' => new StringType(
                description: self::TEXT_FILTER_DESCRIPTION,
            ),
            'after' => new StringType(
                format: StringFormat::dateTime,
                description: self::AFTER_DESCRIPTION,
            ),
        ];

        return new ObjectType(
            properties: $properties,
            required: [
                'parentType',
                'parentId',
            ],
            additionalProperties: false,
        );
    }

    private function prepareOutputSchema(): Schema
    {
        return new ObjectType(
            properties: [
                'records' => $this->prepareOutputListSchema(),
                'total' => new IntegerType(
                    description: <<<'EOT'
                        Total number of records in the search result.
                        EOT
                ),
                'error' => new ObjectType(
                    properties: [
                        'message' => new StringType(
                            description: "Error message.",
                        ),
                        'code' => GroupSchema::createAnyOf(
                            schemas: [
                                new ConstSchema(
                                    value: 400,
                                    description: "Bad request.",
                                ),
                                new ConstSchema(
                                    value: 403,
                                    description: "No 'stream' access. Or other access error.",
                                ),
                                new ConstSchema(
                                    value: 404,
                                    description: "Record not found.",
                                ),
                            ],
                            description: 'Error code.',
                        ),
                    ],
                ),
            ],
        );
    }

    private function prepareOutputListSchema(): Schema
    {
        $properties = [
            Attribute::ID => new StringType(
                title: 'ID',
                description: "Record ID.",
            ),
            Note::FIELD_TYPE => GroupSchema::createAnyOf(
                schemas: [
                    ...$this->getTypeSchemas(),
                    new StringType(
                        description: "Any other type including custom ones.",
                    ),
                ],
                title: 'Type',
                description: "Entry type.",
            ),
            Note::FIELD_POST => Util::wrapWithNull(
                new StringType(
                    title: 'Post',
                    description: "Post body.",
                ),
            ),
            Note::FIELD_IS_INTERNAL => new BooleanType(
                title: 'Is Internal',
                description: "Is the post internal. Internal posts are not visible in portals."
            ),
            'data' => new ObjectType(
                additionalProperties: true,
                title: 'Data',
                description: "Additional data.",
            ),
            'reactionCounts' => new ObjectType(
                properties: [
                    'Like' => new IntegerType(),
                ],
                additionalProperties: new IntegerType(),
                description: "Reaction counts.",
            ),
            'myReactions' => new ArrayType(
                items: new StringType(),
                description: "My reactions.",
            ),
        ];

        return new ArrayType(
            items: new ObjectType(
                properties: $properties,
            ),
            description: "Stream entries.",
        );
    }

    /**
     * @return ConstSchema[]
     */
    private function getTypeSchemas(): array
    {
        return [
            new ConstSchema(
                value: Note::TYPE_POST,
                title: self::TITLE_TYPE_POST,
                description: self::DESC_TYPE_POST,
            ),
            new ConstSchema(
                value: Note::TYPE_UPDATE,
                title: self::TITLE_TYPE_UPDATE,
                description: self::DESC_TYPE_UPDATE,
            ),
            new ConstSchema(
                value: Note::TYPE_CREATE,
                title: self::TITLE_TYPE_CREATE,
                description: self::DESC_TYPE_CREATE,
            ),
            new ConstSchema(
                value: Note::TYPE_ASSIGN,
                title: self::TITLE_TYPE_ASSIGN,
                description: self::DESC_TYPE_ASSIGN,
            ),
            new ConstSchema(
                value: Note::TYPE_EMAIL_RECEIVED,
                title: self::TITLE_TYPE_EMAIL_RECEIVED,
                description: self::DESC_TYPE_EMAIL_RECEIVED,
            ),
            new ConstSchema(
                value: Note::TYPE_EMAIL_SENT,
                title: self::TITLE_TYPE_EMAIL_SENT,
                description: self::DESC_TYPE_EMAIL_SENT,
            ),
        ];
    }

    /**
     * @return string[]
     */
    private function getEntityTypes(RecordStreamData $data): array
    {
        $list = array_filter($data->entityTypes, function ($entityType) {
            return $this->acl->tryCheck($entityType, Acl\Table::ACTION_STREAM);
        });

        return array_values($list);
    }
}
