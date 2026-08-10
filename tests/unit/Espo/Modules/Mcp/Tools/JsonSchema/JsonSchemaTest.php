<?php
/**LICENSE**/

namespace tests\unit\Espo\Modules\Mcp\Tools\JsonSchema;

use Espo\Modules\Mcp\Tools\JsonSchema\Schema;
use Espo\Modules\Mcp\Tools\JsonSchema\Type\BooleanType;
use Espo\Modules\Mcp\Tools\JsonSchema\Type\IntegerType;
use Espo\Modules\Mcp\Tools\JsonSchema\Type\NullType;
use Espo\Modules\Mcp\Tools\JsonSchema\Type\NumberType;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class JsonSchemaTest extends TestCase
{
    #[DataProvider('dataProvider')]
    public function testSchemaNumberType(mixed $expected, Schema $schema): void
    {
        $this->assertEquals($expected, $schema->jsonSerialize());
    }

    /**
     * @return array{0: mixed, 1: Schema}[]
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
                ],
                new NumberType(
                    exclusiveMinimum: 0,
                    exclusiveMaximum: 1.1,
                    multipleOf: 0.01
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
                ],
                new IntegerType(
                    minimum: 0,
                    maximum: 1,
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
                    'type' => 'null',
                ],
                new NullType(),
            ],
        ];
    }
}
