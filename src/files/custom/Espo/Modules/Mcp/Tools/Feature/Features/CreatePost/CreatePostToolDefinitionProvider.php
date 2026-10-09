<?php
/**LICENSE**/

namespace Espo\Modules\Mcp\Tools\Feature\Features\CreatePost;

use Espo\Core\Acl;
use Espo\Core\Utils\Language;
use Espo\Core\Utils\Metadata;
use Espo\Modules\Mcp\Tools\Feature\Data;
use Espo\Modules\Mcp\Tools\Feature\Exceptions\NoUserAccess;
use Espo\Modules\Mcp\Tools\Feature\ToolDefinitionProvider;
use Espo\Modules\Mcp\Tools\JsonSchema\ConstSchema;
use Espo\Modules\Mcp\Tools\JsonSchema\GroupSchema;
use Espo\Modules\Mcp\Tools\JsonSchema\Schema;
use Espo\Modules\Mcp\Tools\JsonSchema\Type\BooleanType;
use Espo\Modules\Mcp\Tools\JsonSchema\Type\ObjectType;
use Espo\Modules\Mcp\Tools\JsonSchema\Type\StringType;
use Espo\Modules\Mcp\Tools\Mcp\Schema\General\RootObjectSchema;
use Espo\Modules\Mcp\Tools\Mcp\Schema\General\RootSchema;
use Espo\Modules\Mcp\Tools\Mcp\Schema\Tool\Tool;
use Espo\Modules\Mcp\Tools\Mcp\Schema\Tool\ToolAnnotations;

/**
 * @implements ToolDefinitionProvider<CreatePostData>
 */
class CreatePostToolDefinitionProvider implements ToolDefinitionProvider
{
    private const string DESCRIPTION =
        "Creates a stream post in a specific record. Can be used for communication or for notes.";

    private const string DESC_PARENT_TYPE = "Parent entity type.";

    private const string DESC_PARENT_ID = "Parent entity ID. " .
        "IDs can be looked up with tools `Find_{EntityType}`.";

    private const string DESC_POST = "Post contents. A message or a note. Markdown is supported.\n\n" .
        "To mention other users, use: `@{userName}`. The {userName} can be obtained with the tool `Find_User`.";

    private const string DESC_IS_INTERNAL =
        "Whether the post is internal. Internal posts are visible only for internal CRM users " .
        "and not visible in portals.";

    private const int POST_MAX_LENGTH = 20000;

    public function __construct(
        private Language $defaultLanguage,
        private Acl $acl,
        private Metadata $metadata,
    ) {}

    public function get(Data $data): Tool
    {
        if ($this->getEntityTypes($data) === []) {
            throw new NoUserAccess("No stream access to any entity type.");
        }

        return new Tool(
            name: CreatePostData::TYPE,
            inputSchema: new RootObjectSchema($this->prepareInputSchema($data)),
            outputSchema: new RootSchema($this->prepareOutputSchema()),
            description: self::DESCRIPTION,
            annotations: new ToolAnnotations(
                readOnlyHint: false,
                destructiveHint: false,
                openWorldHint: false,
            ),
        );
    }

    /**
     * @return string[]
     */
    private function getEntityTypes(CreatePostData $data): array
    {
        $list = array_filter($data->entityTypes, function ($entityType) {
            return $this->acl->tryCheck($entityType, Acl\Table::ACTION_STREAM);
        });

        return array_values($list);
    }

    private function prepareInputSchema(CreatePostData $data): ObjectType
    {
        $entityTypes = $this->getEntityTypes($data);

        $properties = [
            'parentType' => GroupSchema::createAnyOf(
                schemas: array_map(function ($entityType) {
                    return new ConstSchema(
                        value: $entityType,
                        title: $this->defaultLanguage->translateLabel($entityType, 'scopeNames')
                    );
                }, $entityTypes),
                description: self::DESC_PARENT_TYPE,
            ),
            'parentId' => new StringType(
                description: self::DESC_PARENT_ID,
            ),
            'post' => new StringType(
                maxLength: self::POST_MAX_LENGTH,
                description: self::DESC_POST,
            ),
        ];

        $withInternalEntityTypes = $this->filterEntityTypesWithInternalSupport($entityTypes);

        if ($withInternalEntityTypes) {
            $isInternalDescription = self::DESC_IS_INTERNAL .
                " Supported only for the following entity types: " . implode(', ', $withInternalEntityTypes) . ".";

            $properties['isInternal'] = new BooleanType(
                description: $isInternalDescription,
                default: false,
            );
        }

        return new ObjectType(
            properties: $properties,
            required: [
                'parentType',
                'parentId',
                'post',
            ],
            additionalProperties: false,
        );
    }

    private function prepareOutputSchema(): Schema
    {
        return new ObjectType(
            properties: [
                'record' => new ObjectType(
                    properties: [
                        'id' => new StringType(
                            description: "Record ID.",
                        ),
                    ],
                    description: "Created record of `Note` entity type.",
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

    /**
     * @param string[] $entityTypes
     * @return string[]
     */
    private function filterEntityTypesWithInternalSupport(array $entityTypes): array
    {
        $withInternalEntityTypes = array_filter($entityTypes, function ($it) {
            return $this->metadata->get("streamDefs.$it.allowInternalNotes") ?? false;
        });

        return array_values($withInternalEntityTypes);
    }
}
