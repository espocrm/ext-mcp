<?php
/**LICENSE**/

namespace tests\unit\Espo\Modules\Mcp\Tools\Feature\Find;

use Espo\Core\Field\DateTime;
use Espo\Modules\Mcp\Tools\Feature\Find\EntityOutput;
use Espo\Modules\Mcp\Tools\JsonSchema\EnumSchema;
use Espo\Modules\Mcp\Tools\JsonSchema\GroupSchema;
use Espo\Modules\Mcp\Tools\JsonSchema\StringFormat;
use Espo\Modules\Mcp\Tools\JsonSchema\Type\NullType;
use Espo\Modules\Mcp\Tools\JsonSchema\Type\ObjectType;
use Espo\Modules\Mcp\Tools\JsonSchema\Type\StringType;
use Espo\Modules\Mcp\Tools\Schema\Field\Util;
use Espo\ORM\Entity;
use Espo\ORM\Type\AttributeType;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class EntityOutputTest extends TestCase
{
    public function testDatetime(): void
    {
        $entity = $this->createMock(Entity::class);

        $types = [
            ['a1', AttributeType::DATETIME],
        ];

        $entity->method('getAttributeType')
            ->willReturnMap($types);

        $entity->method('getAttributeList')
            ->willReturn(array_column($types, 0));

        $entity->method('getValueMap')
            ->willReturn((object) [
                'a1' => DateTime::fromString('2030-10-10 10:00')->toString(),
            ]);

        $schema = new ObjectType(
            properties: [
                'a1' => new StringType(format: StringFormat::dateTime),
            ],
        );

        $output = new EntityOutput();

        $valueMap = $output->prepare($entity, $schema);

        $this->assertEquals((object) [
            'a1' => '2030-10-10T10:00:00+00:00',
        ], $valueMap);
    }

    public function testUnsetNull(): void
    {
        $entity = $this->createMock(Entity::class);

        $types = [
            ['a1', AttributeType::VARCHAR],
            ['a2', AttributeType::VARCHAR],
            ['a3', AttributeType::VARCHAR],
            ['a4', AttributeType::VARCHAR],
            ['n1', AttributeType::VARCHAR],
            ['n2', AttributeType::VARCHAR],
            ['n3', AttributeType::VARCHAR],
        ];

        $entity->method('getAttributeType')
            ->willReturnMap($types);

        $entity->method('getAttributeList')
            ->willReturn(array_column($types, 0));

        $entity->method('getValueMap')
            ->willReturn((object) [
                'a1' => null,
                'a2' => null,
                'a3' => null,
                'a4' => null,
                'n1' => null,
                'n2' => null,
                'n3' => null,
            ]);

        $schema = new ObjectType(
            properties: [
                'a1' => new StringType(),
                'a2' => new StringType(),
                'a3' => GroupSchema::createAnyOf(
                    schemas: [
                        new StringType(),
                    ],
                ),
                'a4' => new EnumSchema(
                    values: ['Test']
                ),
                'n1' => Util::wrapWithNull(new StringType()),
                'n2' => GroupSchema::createAnyOf(
                    schemas: [
                        new StringType(),
                        new NullType(),
                    ],
                ),
                'n3' => new EnumSchema(
                    values: ['Test', null]
                ),
            ],
        );

        $output = new EntityOutput();

        $valueMap = $output->prepare($entity, $schema);

        $this->assertEquals((object) [
            'n1' => null,
            'n2' => null,
            'n3' => null,
        ], $valueMap);
    }
}
