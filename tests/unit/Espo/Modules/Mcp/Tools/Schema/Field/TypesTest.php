<?php
/**LICENSE**/

namespace tests\unit\Espo\Modules\Mcp\Tools\Schema\Field;

use Espo\Core\ORM\Type\FieldType;
use Espo\Core\Utils\Language;
use Espo\Modules\Mcp\Tools\JsonSchema\ConstSchema;
use Espo\Modules\Mcp\Tools\JsonSchema\GroupSchema;
use Espo\Modules\Mcp\Tools\JsonSchema\Type\ArrayType;
use Espo\Modules\Mcp\Tools\Schema\Field\FieldSchemaProvider\Action;
use Espo\Modules\Mcp\Tools\Schema\Field\FieldSchemaProvider\Params;
use Espo\Modules\Mcp\Tools\Schema\Field\FieldSchemaProvider\Result;
use Espo\Modules\Mcp\Tools\Schema\Field\Types\ArraySchemaProvider;
use Espo\Modules\Mcp\Tools\Schema\Util\EnumOptionsProvider;
use Espo\Modules\Mcp\Tools\Schema\Util\EnumOptionTranslator;
use Espo\ORM\Defs;
use Espo\ORM\Defs\EntityDefs;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class TypesTest extends TestCase
{
    public function testArray(): void
    {
        $entityDefs = self::createEntityDefs(
            entityType: 'Test',
            field: 'test',
            type: FieldType::ARRAY,
            required: true,
        );

        $language = $this->createLanguage(
            fields: [
                'test' => 'Test Field',
            ],
        );

        $enumOptionsProvider = $this->createEnumOptionsProvider(
            fieldDefs: $entityDefs->getField('test'),
            options: ['a', 'b'],
        );

        $enumOptionTranslator = $this->createEnumOptionTranslator([
            'test' => [
                'a' => 'A',
                'b' => 'B',
            ]
        ]);

        $provider = new ArraySchemaProvider(
            ormDefs: $this->createOrmDefs($entityDefs),
            defaultLanguage: $language,
            enumOptionsProvider: $enumOptionsProvider,
            enumOptionTranslator: $enumOptionTranslator,
        );

        $result = $provider->get(
            params: new Params(
                entityType: 'Test',
                field: 'test',
                action: Action::Find,
            ),
        );

        $this->assertEquals(
            new Result(
                properties: [
                    'test' => new ArrayType(
                        items: GroupSchema::createAnyOf(
                            schemas: [
                                new ConstSchema(
                                    value: 'a',
                                    title: 'A',
                                ),
                                new ConstSchema(
                                    value: 'b',
                                    title: 'B',
                                ),
                            ],
                        ),
                        uniqueItems: true,
                        title: 'Test Field',
                    ),
                ],
            ),
            $result
        );
    }

    private function createEnumOptionsProvider(Defs\FieldDefs $fieldDefs, array $options): EnumOptionsProvider
    {
        $enumOptionsProvider = $this->createMock(EnumOptionsProvider::class);

        $enumOptionsProvider->method('get')
            ->with($fieldDefs)
            ->willReturn($options);

        return $enumOptionsProvider;
    }

    /**
     * @param array<string, array<string, string>> $options
     */
    private function createEnumOptionTranslator(array $options = []): EnumOptionTranslator
    {
        $translator = $this->createMock(EnumOptionTranslator::class);

        $translator->method('translate')
            ->willReturnCallback(function (string $value, string $field) use ($options) {
                return $options[$field][$value] ?? $value;
            });

        return $translator;
    }

    /**
     * @param array<string, string> $fields
     * @param array<string, array<string, string>> $options
     */
    private function createLanguage(array $fields = [], array $options = []): Language
    {
        $language = $this->createMock(Language::class);

        $language->method('translateLabel')
            ->willReturnCallback(function (string $name) use ($fields) {
                return $fields[$name] ?? $name;
            });

        $language->method('translateOption')
            ->willReturnCallback(function (string $value, string $field) use ($options) {
                return $options[$field][$value] ?? $value;
            });

        return $language;
    }

    private function createOrmDefs(EntityDefs $entityDefs): Defs
    {
        $defs = $this->createMock(Defs::class);

        $defs->method('getEntity')
            ->willReturnMap([
                [$entityDefs->getName(), $entityDefs],
            ]);

        return $defs;
    }

    /**
     * @param array<string, mixed> $params
     */
    private static function createEntityDefs(
        string $entityType,
        string $field,
        string $type,
        bool $required = false,
        array $params = [],
    ): EntityDefs {

        return EntityDefs::fromRaw(
            raw: [
                'fields' => [
                    $field => [
                        'type' => $type,
                        'required' => $required,
                        ...$params,
                    ],
                ],
            ],
            name: $entityType,
        );
    }
}
