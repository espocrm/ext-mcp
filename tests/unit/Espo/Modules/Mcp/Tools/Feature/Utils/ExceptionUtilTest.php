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

namespace tests\unit\Espo\Modules\Mcp\Tools\Feature\Utils;

use Espo\Core\Exceptions\Conflict;
use Espo\Core\Exceptions\Error\Body;
use Espo\Core\Utils\Language;
use Espo\Modules\Mcp\Tools\Feature\Utils\ExceptionUtil;
use Espo\Modules\Mcp\Tools\Feature\Utils\ResourceLinkPreparator;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class ExceptionUtilTest extends TestCase
{
    public function testGetBodyMessage(): void
    {
        $language = $this->createMock(Language::class);

        $language
            ->expects(self::exactly(2))
            ->method('translateLabel')
            ->willReturnMap([
                ['test', 'messages', 'Test', "Hello {one}."],
            ]);

        $e = Conflict::createWithBody('test', Body::create()->withMessageTranslation(
            label: 'test',
            scope: 'Test',
            data: [
                'one' => '1',
            ],
        ));

        $util = new ExceptionUtil(
            defaultLanguage: $language,
            resourceLinkPreparator: $this->createMock(ResourceLinkPreparator::class),
        );

        $this->assertEquals("Hello 1.", $util->getBodyMessage($e));
        $this->assertEquals("Test. Hello 1.", $util->appendBodyMessage("Test.", $e));
    }
}
