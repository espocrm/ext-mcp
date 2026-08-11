<?php
/**LICENSE**/

namespace tests\unit\Espo\Modules\Mcp\Tools\JsonSchema;

use Espo\Modules\Mcp\Tools\JsonSchema\ConstSchema;
use Espo\Modules\Mcp\Tools\JsonSchema\EnumSchema;
use Espo\Modules\Mcp\Tools\JsonSchema\GroupKeyword;
use Espo\Modules\Mcp\Tools\JsonSchema\GroupSchema;
use Espo\Modules\Mcp\Tools\JsonSchema\NotSchema;
use Espo\Modules\Mcp\Tools\JsonSchema\Schema;
use Espo\Modules\Mcp\Tools\JsonSchema\StringFormat;
use Espo\Modules\Mcp\Tools\JsonSchema\Type\ArrayType;
use Espo\Modules\Mcp\Tools\JsonSchema\Type\BooleanType;
use Espo\Modules\Mcp\Tools\JsonSchema\Type\IntegerType;
use Espo\Modules\Mcp\Tools\JsonSchema\Type\NullType;
use Espo\Modules\Mcp\Tools\JsonSchema\Type\NumberType;
use Espo\Modules\Mcp\Tools\JsonSchema\Type\ObjectType;
use Espo\Modules\Mcp\Tools\JsonSchema\Type\StringType;
use Espo\Modules\Mcp\Tools\JsonSchema\UnionTypeSchema;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use stdClass;
use Throwable;
use UnexpectedValueException;

class JsonSchemaTest extends TestCase
{
    #[DataProvider('dataProvider')]
    public function testSchemaNumberType(mixed $expected, Schema $schema): void
    {
        $this->assertEquals($expected, $schema->jsonSerialize());
    }

    /**
     * @return array{
     *     0: stdClass|null,
     *     1: Schema,
     *     2?: Throwable,
     * }[]
     */
    public static function dataProvider(): array
    {
        return [
            [
                (object) [
                    'type' => 'number',
                    'minimum' => 0,
                    'maximum' => 1,
                    'title' => 'Test',
                    'description' => 'Test.'
                ],
                new NumberType(
                    minimum: 0,
                    maximum: 1,
                    title: 'Test',
                    description: 'Test.',
                )
            ],
            [
                (object) [
                    'type' => 'number',
                    'exclusiveMinimum' => 0,
                    'exclusiveMaximum' => 1.1,
                    'multipleOf' => 0.01,
                    'default' => 0,
                ],
                new NumberType(
                    exclusiveMinimum: 0,
                    exclusiveMaximum: 1.1,
                    multipleOf: 0.01,
                    default: 0,
                )
            ],
            [
                (object) [
                    'type' => 'integer',
                    'exclusiveMinimum' => 0,
                    'exclusiveMaximum' => 2,
                    'multipleOf' => 2,
                ],
                new IntegerType(
                    exclusiveMinimum: 0,
                    exclusiveMaximum: 2,
                    multipleOf: 2,
                ),
            ],
            [
                (object) [
                    'type' => 'integer',
                    'minimum' => 0,
                    'maximum' => 1,
                    'default' => 0,
                ],
                new IntegerType(
                    minimum: 0,
                    maximum: 1,
                    default: 0,
                ),
            ],
            [
                (object) [
                    'type' => 'boolean',
                ],
                new BooleanType(),
            ],
            [
                (object) [
                    'type' => 'boolean',
                    'default' => true,
                ],
                new BooleanType(
                    default: true,
                ),
            ],
            [
                (object) [
                    'type' => 'null',
                ],
                new NullType(),
            ],
            [
                (object) [
                    'type' => 'string',
                    'minLength' => 1,
                    'maxLength' => 100,
                    'pattern' => '[a-z]+',
                    'default' => 'abc',
                ],
                new StringType(
                    minLength: 1,
                    maxLength: 100,
                    pattern: '[a-z]+',
                    default: 'abc',
                ),
            ],
            [
                (object) [
                    'type' => 'string',
                    'format' => 'date',
                ],
                new StringType(
                    format: StringFormat::date,
                ),
            ],
            [
                (object) [
                    'type' => 'array',
                    'items' => (object) [
                        'type' => 'string',
                    ],
                    'minItems' => 0,
                    'maxItems' => 2,
                    'uniqueItems' => true,
                ],
                new ArrayType(
                    items: new StringType(),
                    minItems: 0,
                    maxItems: 2,
                    uniqueItems: true,
                ),
            ],
            [
                (object) [
                    'type' => 'object',
                    'properties' => (object) [
                        'a' => (object) [
                            'type' => 'string',
                        ],
                        'b' => (object) [
                            'type' => 'string',
                        ],
                    ],
                    'required' => ['a'],
                ],
                new ObjectType(
                    properties: [
                        'a' => new StringType(),
                        'b' => new StringType(),
                    ],
                    required: ['a'],
                ),
            ],
            [
                (object) [
                    'type' => 'object',
                    'properties' => (object) [
                        'a' => (object) [
                            'type' => 'string',
                        ],
                        'b' => (object) [
                            'type' => 'string',
                        ],
                    ],
                    'required' => ['a'],
                ],
                new ObjectType(
                    properties: [
                        'a' => new StringType(),
                        'b' => new StringType(),
                    ],
                    required: ['a'],
                ),
            ],
            [
                (object) [
                    'type' => 'object',
                    'additionalProperties' => true,
                ],
                new ObjectType(
                    additionalProperties: true,
                ),
            ],
            [
                (object) [
                    'type' => 'object',
                    'additionalProperties' => (object) [
                        'type' => 'string',
                    ],
                ],
                new ObjectType(
                    additionalProperties: new StringType(),
                ),
            ],
            [
                (object) [
                    'const' => 1,
                ],
                new ConstSchema(value: 1),
            ],
            [
                (object) [
                    'const' => (object) [
                        'a' => 'b'
                    ],
                ],
                new ConstSchema(
                    value: (object) [
                        'a' => 'b'
                    ],
                ),
            ],
            [
                (object) [
                    'enum' => [1, 2],
                ],
                new EnumSchema(values: [1, 2]),
            ],
            [
                (object) [
                    'enum' => [
                        (object) [
                            'a' => 1
                        ],
                        (object) [
                            'b' => 2
                        ],
                    ],
                ],
                new EnumSchema(
                    values: [
                        (object) [
                            'a' => 1
                        ],
                        (object) [
                            'b' => 2
                        ],
                    ]
                ),
            ],
            [
                (object) [
                    'anyOf' => [
                        (new StringType())->jsonSerialize(),
                        (new NumberType())->jsonSerialize(),
                    ],
                ],
                new GroupSchema(
                    keyword: GroupKeyword::anyOf,
                    schemas: [
                        new StringType(),
                        new NumberType(),
                    ],
                )
            ],
            [
                (object) [
                    'anyOf' => [
                        (new StringType())->jsonSerialize(),
                        (new NumberType())->jsonSerialize(),
                    ],
                    'default' => 10,
                ],
                new GroupSchema(
                    keyword: GroupKeyword::anyOf,
                    schemas: [
                        new StringType(),
                        new NumberType(),
                    ],
                    default: 10,
                )
            ],
            [
                (object) [
                    'not' => (new StringType())->jsonSerialize(),
                ],
                new NotSchema(
                    schema: new StringType()
                ),
            ],
            [
                (object) [
                    'type' => ['string', 'null'],
                    'minLength' => 1,
                ],
                new UnionTypeSchema(
                    schemas: [
                        new StringType(
                            minLength: 1,
                        ),
                        new NullType()
                    ],
                ),
            ],
        ];
    }

    public function testUnionTypeSameType(): void
    {
        $this->expectException(UnexpectedValueException::class);

        new UnionTypeSchema(
            schemas: [
                new StringType(
                    minLength: 1,
                ),
                new StringType(),
            ],
        );
    }

    public function testUnionTypeSameProperty(): void
    {
        $this->expectException(UnexpectedValueException::class);

        new UnionTypeSchema(
            schemas: [
                new IntegerType(
                    minimum: 1,
                ),
                new NumberType(
                    minimum: 1,
                ),
            ],
        );
    }
}
