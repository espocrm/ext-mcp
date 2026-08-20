<?php
/**LICENSE**/

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
