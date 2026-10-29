<?php
/************************************************************************
* This file is part of MCP extension for EspoCRM.
*
* MCP extension for EspoCRM.
* Copyright (C) 2026 EspoCRM, Inc.
* Website: https://www.espocrm.com
*
* This program is free software: you can redistribute it and/or modify
* it under the terms of the GNU Affero General Public License as published by
* the Free Software Foundation, either version 3 of the License, or
* (at your option) any later version.
*
* This program is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
* GNU Affero General Public License for more details.
*
* You should have received a copy of the GNU Affero General Public License
* along with this program. If not, see <https://www.gnu.org/licenses/>.
*
* The interactive user interfaces in modified source and object code versions
* of this program must display Appropriate Legal Notices, as required under
* Section 5 of the GNU Affero General Public License version 3.
*
* In accordance with Section 7(b) of the GNU Affero General Public License version 3,
* these Appropriate Legal Notices must retain the display of the "EspoCRM" word.
************************************************************************/

namespace tests\unit\Espo\Modules\Mcp\Tools\Feature\Find;

use Espo\Core\Field\DateTime;
use Espo\Modules\Mcp\Tools\Feature\Features\Find\EntityOutput;
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
