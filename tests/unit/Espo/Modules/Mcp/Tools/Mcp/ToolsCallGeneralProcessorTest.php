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

namespace tests\unit\Espo\Modules\Mcp\Tools\Mcp;

use Espo\Modules\Mcp\Tools\Feature\DataFactory;
use Espo\Modules\Mcp\Tools\JsonSchema\StringFormat;
use Espo\Modules\Mcp\Tools\JsonSchema\Type\ObjectType;
use Espo\Modules\Mcp\Tools\JsonSchema\Type\StringType;
use Espo\Modules\Mcp\Tools\Mcp\JsonSchemaValidator\Validator;
use Espo\Modules\Mcp\Tools\Mcp\Schema\General\RootObjectSchema;
use Espo\Modules\Mcp\Tools\Mcp\Schema\Tool\CallToolRequestParams;
use Espo\Modules\Mcp\Tools\Mcp\Schema\Tool\Tool;
use Espo\Modules\Mcp\Tools\Mcp\Tool\ToolEnvelope;
use Espo\Modules\Mcp\Tools\Mcp\Tool\ToolProvider;
use Espo\Modules\Mcp\Tools\Mcp\ToolsCall\ToolProcessorFactory;
use Espo\Modules\Mcp\Tools\Mcp\ToolsCall\ToolsCallGeneralProcessor;
use Espo\ORM\EntityManager;
use PHPUnit\Framework\TestCase;

class ToolsCallGeneralProcessorTest extends TestCase
{
    public function testInvalidDateTimeReturnsReadableToolError(): void
    {
        $tool = new Tool(
            name: 'Create_Meeting',
            inputSchema: new RootObjectSchema(new ObjectType(properties: [
                'record' => new ObjectType(properties: [
                    'dateStart' => new StringType(format: StringFormat::dateTime),
                ]),
            ])),
        );

        $toolProvider = $this->createStub(ToolProvider::class);
        $toolProvider->method('get')->willReturn(new ToolEnvelope($tool, 'meeting-feature'));

        $processorFactory = $this->createMock(ToolProcessorFactory::class);
        $processorFactory->expects($this->never())->method('create');

        $processor = new ToolsCallGeneralProcessor(
            $toolProvider,
            new Validator(),
            $processorFactory,
            $this->createStub(EntityManager::class),
            $this->createStub(DataFactory::class),
        );

        $result = $processor->process(new CallToolRequestParams(
            name: $tool->name,
            arguments: (object) [
                'record' => (object) ['dateStart' => '2030-01-02 10:00:00'],
            ],
        ))->jsonSerialize();

        $this->assertTrue($result->isError);
        $this->assertSame('text', $result->content[0]->type);
        $this->assertStringContainsString('/record/dateStart', $result->content[0]->text);
        $this->assertStringContainsString('2030-01-02T10:00:00+00:00', $result->content[0]->text);
    }
}
