<?php
/**LICENSE**/

namespace tests\unit\Espo\Modules\Mcp\Tools\Schema\Field;

use Espo\Core\Currency\ConfigDataProvider as CurrencyConfig;
use Espo\Core\ORM\Type\FieldType;
use Espo\Core\Utils\Language;
use Espo\Modules\Mcp\Tools\JsonSchema\ConstSchema;
use Espo\Modules\Mcp\Tools\JsonSchema\EnumSchema;
use Espo\Modules\Mcp\Tools\JsonSchema\GroupSchema;
use Espo\Modules\Mcp\Tools\JsonSchema\StringFormat;
use Espo\Modules\Mcp\Tools\JsonSchema\Type\ArrayType;
use Espo\Modules\Mcp\Tools\JsonSchema\Type\BooleanType;
use Espo\Modules\Mcp\Tools\JsonSchema\Type\IntegerType;
use Espo\Modules\Mcp\Tools\JsonSchema\Type\NullType;
use Espo\Modules\Mcp\Tools\JsonSchema\Type\NumberType;
use Espo\Modules\Mcp\Tools\JsonSchema\Type\StringType;
use Espo\Modules\Mcp\Tools\JsonSchema\UnionTypeSchema;
use Espo\Modules\Mcp\Tools\Schema\Field\DateHelper;
use Espo\Modules\Mcp\Tools\Schema\Field\FieldSchemaProvider\Action;
use Espo\Modules\Mcp\Tools\Schema\Field\FieldSchemaProvider\Params;
use Espo\Modules\Mcp\Tools\Schema\Field\FieldSchemaProvider\Result;
use Espo\Modules\Mcp\Tools\Schema\Field\Types\ArraySchemaProvider;
use Espo\Modules\Mcp\Tools\Schema\Field\Types\BoolSchemaProvider;
use Espo\Modules\Mcp\Tools\Schema\Field\Types\CurrencyConvertedSchemaProvider;
use Espo\Modules\Mcp\Tools\Schema\Field\Types\CurrencySchemaProvider;
use Espo\Modules\Mcp\Tools\Schema\Field\Types\DateSchemaProvider;
use Espo\Modules\Mcp\Tools\Schema\Field\Types\DatetimeOptionalSchemaProvider;
use Espo\Modules\Mcp\Tools\Schema\Field\Types\DatetimeSchemaProvider;
use Espo\Modules\Mcp\Tools\Schema\Field\Types\DecimalSchemaProvider;
use Espo\Modules\Mcp\Tools\Schema\Field\Types\DurationSchemaProvider;
use Espo\Modules\Mcp\Tools\Schema\Field\Types\EmailSchemaProvider;
use Espo\Modules\Mcp\Tools\Schema\Field\Types\EnumSchemaProvider;
use Espo\Modules\Mcp\Tools\Schema\Field\Types\FloatSchemaProvider;
use Espo\Modules\Mcp\Tools\Schema\Field\Types\IdSchemaProvider;
use Espo\Modules\Mcp\Tools\Schema\Field\Types\IntSchemaProvider;
use Espo\Modules\Mcp\Tools\Schema\Util\EnumOptionsProvider;
use Espo\Modules\Mcp\Tools\Schema\Util\EnumOptionTranslator;
use Espo\ORM\Defs;
use Espo\ORM\Defs\EntityDefs;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class TypesTest extends TestCase
{
    public function testBool(): void
    {
        $language = $this->createLanguage(
            fields: [
                'test' => 'Field',
            ],
        );

        $provider = new BoolSchemaProvider(
            defaultLanguage: $language,
        );

        $this->assertEquals(
            expected: new Result(
                properties: [
                    'test' => new BooleanType(
                        title: 'Field'
                    ),
                ],
            ),
            actual: $provider->get(
                params: new Params(
                    entityType: 'Test',
                    field: 'test',
                    action: Action::Update,
                ),
            ),
        );
    }

    public function testCurrencyNumeric(): void
    {
        $provider = new CurrencySchemaProvider(
            ormDefs: $this->createOrmDefs(
                entityDefs: self::createEntityDefs(
                    entityType: 'Test',
                    field: 'test',
                    type: FieldType::CURRENCY,
                    params: [
                        'min' => 0,
                    ],
                ),
            ),
            defaultLanguage: $this->createLanguage(
                fields: [
                    'test' => 'Field',
                ],
            ),
            currencyConfig: $this->createCurrencyConfig('EUR', ['EUR', 'USD']),
        );

        $this->assertEquals(
            expected: new Result(
                properties: [
                    'test' => new UnionTypeSchema(
                        schemas: [
                            new NumberType(
                                minimum: 0,
                                title: 'Field',
                                description: "Amount. Currency code is set in the `testCurrency` field.",
                            ),
                            new NullType(),
                        ],
                    ),
                    'testCurrency' => new EnumSchema(
                        values: ['EUR', 'USD', null],
                        description: "Currency code for the `test` field. " .
                            "Use `null` if the `test` field is null.",
                    ),
                ],
                suppress: ['testCurrency'],
            ),
            actual: $provider->get(
                params: new Params(
                    entityType: 'Test',
                    field: 'test',
                    action: Action::Update,
                ),
            ),
        );
    }

    public function testDate(): void
    {
        $provider = new DateSchemaProvider(
            ormDefs: $this->createOrmDefs(
                entityDefs: self::createEntityDefs(
                    entityType: 'Test',
                    field: 'test',
                    type: FieldType::DATE,
                    required: true,
                    params: [
                        'before' => 'testAnother',
                    ],
                ),
            ),
            defaultLanguage: $this->createLanguage(
                fields: [
                    'test' => 'Field',
                ],
            ),
            dateHelper: new DateHelper(),
        );

        $this->assertEquals(
            expected: new Result(
                properties: [
                    'test' => new StringType(
                        format: StringFormat::date,
                        title: 'Field',
                        description: "If set, must be earlier than the `testAnother` field.",
                    ),
                ],
                required: ['test'],
            ),
            actual: $provider->get(
                params: new Params(
                    entityType: 'Test',
                    field: 'test',
                    action: Action::Update,
                ),
            ),
        );
    }

    public function testDatetime(): void
    {
        $provider = new DatetimeSchemaProvider(
            ormDefs: $this->createOrmDefs(
                entityDefs: self::createEntityDefs(
                    entityType: 'Test',
                    field: 'test',
                    type: FieldType::DATETIME,
                    required: true,
                    params: [
                        'after' => 'testAnother',
                    ],
                ),
            ),
            defaultLanguage: $this->createLanguage(
                fields: [
                    'test' => 'Field',
                ],
            ),
            dateHelper: new DateHelper(),
        );

        $this->assertEquals(
            expected: new Result(
                properties: [
                    'test' => new StringType(
                        format: StringFormat::dateTime,
                        title: 'Field',
                        description: "If set, must be later than the `testAnother` field.",
                    ),
                ],
                required: ['test'],
            ),
            actual: $provider->get(
                params: new Params(
                    entityType: 'Test',
                    field: 'test',
                    action: Action::Update,
                ),
            ),
        );
    }

    public function testDatetimeOptional(): void
    {
        $language = $this->createLanguage(
            fields: [
                'test' => 'Field',
                'testDate' => 'Field (Date)',
            ],
        );

        $provider = new DatetimeOptionalSchemaProvider(
            defaultLanguage: $language,
            datetimeSchemaProvider: new DatetimeSchemaProvider(
                ormDefs: $this->createOrmDefs(
                    entityDefs: self::createEntityDefs(
                        entityType: 'Test',
                        field: 'test',
                        type: FieldType::DATETIME_OPTIONAL,
                        required: true,
                        params: [
                            'after' => 'testAnother',
                        ],
                    ),
                ),
                defaultLanguage: $language,
                dateHelper: new DateHelper(),
            ),
        );

        $this->assertEquals(
            expected: new Result(
                properties: [
                    'test' => new StringType(
                        format: StringFormat::dateTime,
                        title: 'Field',
                        description: "If set, must be later than the `testAnother` field.",
                    ),
                    'testDate' => new UnionTypeSchema(
                        schemas: [
                            new StringType(
                                format: StringFormat::date,
                                title: 'Field (Date)',
                                description: "Is set only when `test` represents all-day (the time part is omitted). " .
                                    "Should be `null` otherwise.",
                            ),
                            new NullType(),
                        ],
                    ),
                ],
                required: ['test'],
                suppress: ['testDate']
            ),
            actual: $provider->get(
                params: new Params(
                    entityType: 'Test',
                    field: 'test',
                    action: Action::Update,
                ),
            ),
        );
    }

    public function testDecimal(): void
    {
        $provider = new DecimalSchemaProvider(
            ormDefs: $this->createOrmDefs(
                entityDefs: self::createEntityDefs(
                    entityType: 'Test',
                    field: 'test',
                    type: FieldType::DECIMAL,
                    required: true,
                    params: [
                        'min' => 0,
                        'max' => 10,
                    ],
                ),
            ),
            defaultLanguage: $this->createLanguage(
                fields: [
                    'test' => 'Field',
                ],
            ),
        );

        $this->assertEquals(
            expected: new Result(
                properties: [
                    'test' => new StringType(
                        pattern: "^-?\\d+(?:\\.\\d+)?$",
                        title: 'Field',
                        description: "A decimal number represented as string. " .
                            "Min value: `0`. Max value: `10`.",
                    ),
                ],
                required: [
                    'test',
                ],
            ),
            actual: $provider->get(
                params: new Params(
                    entityType: 'Test',
                    field: 'test',
                    action: Action::Update,
                ),
            ),
        );
    }

    public function testCurrencyDecimal(): void
    {
        $provider = new CurrencySchemaProvider(
            ormDefs: $this->createOrmDefs(
                entityDefs: self::createEntityDefs(
                    entityType: 'Test',
                    field: 'test',
                    type: FieldType::CURRENCY,
                    required: true,
                    params: [
                        'decimal' => true,
                        'min' => 0,
                    ],
                ),
            ),
            defaultLanguage: $this->createLanguage(
                fields: [
                    'test' => 'Field',
                ],
            ),
            currencyConfig: $this->createCurrencyConfig('EUR', ['EUR', 'USD']),
        );

        $this->assertEquals(
            expected: new Result(
                properties: [
                    'test' => new StringType(
                        pattern: "^-?\\d+(?:\\.\\d+)?$",
                        title: 'Field',
                        description: "Amount. Currency code is set in the `testCurrency` field. " .
                            "Min value: `0`.",
                    ),
                    'testCurrency' => new EnumSchema(
                        values: ['EUR', 'USD'],
                        description: "Currency code for the `test` field.",
                        default: 'EUR',
                    ),
                ],
                required: [
                    'test',
                    'testCurrency',
                ],
                suppress: ['testCurrency'],
            ),
            actual: $provider->get(
                params: new Params(
                    entityType: 'Test',
                    field: 'test',
                    action: Action::Update,
                ),
            ),
        );
    }

    public function testCurrencyConverted(): void
    {
        $provider = new CurrencyConvertedSchemaProvider(
            ormDefs: $this->createOrmDefs(
                entityDefs: self::createEntityDefs(
                    entityType: 'Test',
                    field: 'test',
                    type: FieldType::CURRENCY_CONVERTED,
                ),
            ),
            defaultLanguage: $this->createLanguage(
                fields: [
                    'testConverted' => 'Field Converted',
                ],
            ),
            currencyConfig: $this->createCurrencyConfig('EUR'),
        );

        $this->assertEquals(
            expected: new Result(
                properties: [
                    'testConverted' => new UnionTypeSchema(
                        schemas: [
                            new NumberType(
                                title: 'Field Converted',
                                description: "Amount of `test` field converted to EUR currency.",
                            ),
                            new NullType(),
                        ]
                    ),
                ],
            ),
            actual: $provider->get(
                params: new Params(
                    entityType: 'Test',
                    field: 'testConverted',
                    action: Action::Read,
                ),
            ),
        );
    }

    public function testArray(): void
    {
        $entityDefs = self::createEntityDefs(
            entityType: 'Test',
            field: 'test',
            type: FieldType::ARRAY,
            required: true,
        );

        $provider = new ArraySchemaProvider(
            ormDefs: $this->createOrmDefs(
                entityDefs: $entityDefs,
            ),
            defaultLanguage: $this->createLanguage(
                fields: [
                    'test' => 'Field',
                ],
            ),
            enumOptionsProvider: $this->createEnumOptionsProvider(
                fieldDefs: $entityDefs->getField('test'),
                options: ['a', 'b'],
            ),
            enumOptionTranslator: $this->createEnumOptionTranslator([
                'test' => [
                    'a' => 'A',
                    'b' => 'B',
                ]
            ]),
        );

        $this->assertEquals(
            expected: new Result(
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
                        title: 'Field',
                    ),
                ],
            ),
            actual: $provider->get(
                params: new Params(
                    entityType: 'Test',
                    field: 'test',
                    action: Action::Find,
                ),
            ),
        );

        $this->assertEquals(
            expected: new Result(
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
                        minItems: 1,
                        uniqueItems: true,
                        title: 'Field',
                    ),
                ],
                required: ['test'],
            ),
            actual: $provider->get(
                params: new Params(
                    entityType: 'Test',
                    field: 'test',
                    action: Action::Update,
                ),
            ),
        );
    }

    public function testEnumRequiredWithDefault(): void
    {
        $entityDefs = self::createEntityDefs(
            entityType: 'Test',
            field: 'test',
            type: FieldType::ENUM,
            required: true,
            params: [
                'default' => 'a',
            ],
        );

        $provider = new EnumSchemaProvider(
            ormDefs: $this->createOrmDefs(
                entityDefs: $entityDefs,
            ),
            defaultLanguage: $this->createLanguage(
                fields: [
                    'test' => 'Field',
                ],
            ),
            enumOptionsProvider: $this->createEnumOptionsProvider(
                fieldDefs: $entityDefs->getField('test'),
                options: ['a', 'b'],
            ),
            enumOptionTranslator: $this->createEnumOptionTranslator([
                'test' => [
                    'a' => 'A',
                    'b' => 'B',
                ]
            ]),
        );

        $this->assertEquals(
            expected: new Result(
                properties: [
                    'test' => GroupSchema::createAnyOf(
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
                        title: 'Field',
                        default: 'a',
                    ),
                ],
                required: [],
            ),
            actual: $provider->get(
                params: new Params(
                    entityType: 'Test',
                    field: 'test',
                    action: Action::Update,
                ),
            ),
        );
    }

    public function testEnumRequiredNoDefault(): void
    {
        $entityDefs = self::createEntityDefs(
            entityType: 'Test',
            field: 'test',
            type: FieldType::ENUM,
            required: true,
        );

        $provider = new EnumSchemaProvider(
            ormDefs: $this->createOrmDefs(
                entityDefs: $entityDefs,
            ),
            defaultLanguage: $this->createLanguage(
                fields: [
                    'test' => 'Field',
                ],
            ),
            enumOptionsProvider: $this->createEnumOptionsProvider(
                fieldDefs: $entityDefs->getField('test'),
                options: ['a', 'b'],
            ),
            enumOptionTranslator: $this->createEnumOptionTranslator([
                'test' => [
                    'a' => 'A',
                    'b' => 'B',
                ]
            ]),
        );

        $this->assertEquals(
            expected: new Result(
                properties: [
                    'test' => GroupSchema::createAnyOf(
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
                        title: 'Field',
                    ),
                ],
                required: [
                    'test',
                ],
            ),
            actual: $provider->get(
                params: new Params(
                    entityType: 'Test',
                    field: 'test',
                    action: Action::Update,
                ),
            ),
        );
    }

    public function testEnumNotRequired(): void
    {
        $entityDefs = self::createEntityDefs(
            entityType: 'Test',
            field: 'test',
            type: FieldType::ENUM,
        );

        $provider = new EnumSchemaProvider(
            ormDefs: $this->createOrmDefs(
                entityDefs: $entityDefs,
            ),
            defaultLanguage: $this->createLanguage(
                fields: [
                    'test' => 'Field',
                ],
            ),
            enumOptionsProvider: $this->createEnumOptionsProvider(
                fieldDefs: $entityDefs->getField('test'),
                options: ['', 'a', 'b'],
            ),
            enumOptionTranslator: $this->createEnumOptionTranslator([
                'test' => [
                    'a' => 'A',
                    'b' => 'B',
                ]
            ]),
        );

        $this->assertEquals(
            expected: new Result(
                properties: [
                    'test' => GroupSchema::createAnyOf(
                        schemas: [
                            new ConstSchema(
                                value: 'a',
                                title: 'A',
                            ),
                            new ConstSchema(
                                value: 'b',
                                title: 'B',
                            ),
                            new ConstSchema(
                                value: null,
                            ),
                        ],
                        title: 'Field',
                    ),
                ],
            ),
            actual: $provider->get(
                params: new Params(
                    entityType: 'Test',
                    field: 'test',
                    action: Action::Update,
                ),
            ),
        );
    }

    public function testFloat(): void
    {
        $provider = new FloatSchemaProvider(
            ormDefs: $this->createOrmDefs(
                entityDefs: self::createEntityDefs(
                    entityType: 'Test',
                    field: 'test',
                    type: FieldType::FLOAT,
                    required: true,
                ),
            ),
            defaultLanguage: $this->createLanguage(
                fields: [
                    'test' => 'Field',
                ],
            ),
        );

        $this->assertEquals(
            expected: new Result(
                properties: [
                    'test' => new NumberType(
                        title: 'Field',
                    ),
                ],
                required: [
                    'test',
                ],
            ),
            actual: $provider->get(
                params: new Params(
                    entityType: 'Test',
                    field: 'test',
                    action: Action::Update,
                ),
            ),
        );
    }

    public function testInt(): void
    {
        $provider = new IntSchemaProvider(
            ormDefs: $this->createOrmDefs(
                entityDefs: self::createEntityDefs(
                    entityType: 'Test',
                    field: 'test',
                    type: FieldType::INT,
                    required: true,
                    params: [
                        'min' => 0,
                        'max' => 100,
                    ],
                ),
            ),
            defaultLanguage: $this->createLanguage(
                fields: [
                    'test' => 'Field',
                ],
            ),
        );

        $this->assertEquals(
            expected: new Result(
                properties: [
                    'test' => new IntegerType(
                        minimum: 0,
                        maximum: 100,
                        title: 'Field',
                    ),
                ],
                required: [
                    'test',
                ],
            ),
            actual: $provider->get(
                params: new Params(
                    entityType: 'Test',
                    field: 'test',
                    action: Action::Update,
                ),
            ),
        );
    }

    public function testId(): void
    {
        $provider = new IdSchemaProvider(
            ormDefs: $this->createOrmDefs(
                entityDefs: self::createEntityDefs(
                    entityType: 'Test',
                    field: 'id',
                    type: 'id',
                ),
            ),
        );

        $this->assertEquals(
            expected: new Result(
                properties: [
                    'id' => new StringType(
                        description: 'Record ID.',
                    ),
                ],
            ),
            actual: $provider->get(
                params: new Params(
                    entityType: 'Test',
                    field: 'id',
                    action: Action::Read,
                ),
            ),
        );
    }

    public function testDuration(): void
    {
        $provider = new DurationSchemaProvider(
            ormDefs: $this->createOrmDefs(
                entityDefs: self::createEntityDefs(
                    entityType: 'Test',
                    field: 'test',
                    type: 'duration',
                    required: true,
                    params: [
                        'default' => 100,
                    ]
                ),
            ),
            defaultLanguage: $this->createLanguage(
                fields: [
                    'test' => 'Field',
                ],
            ),
        );

        $this->assertEquals(
            expected: new Result(
                properties: [
                    'test' => new IntegerType(
                        title: 'Field',
                        description: "Duration in seconds. `3600` is 1h, `1800` is 30m, `900` is 15m.",
                        default: 100,
                    ),
                ],
                required: [],
            ),
            actual: $provider->get(
                params: new Params(
                    entityType: 'Test',
                    field: 'test',
                    action: Action::Update,
                ),
            ),
        );
    }

    public function testEmail(): void
    {
        $provider = new EmailSchemaProvider(
            ormDefs: $this->createOrmDefs(
                entityDefs: self::createEntityDefs(
                    entityType: 'Test',
                    field: 'test',
                    type: FieldType::EMAIL,
                    required: true,
                ),
            ),
            defaultLanguage: $this->createLanguage(
                fields: [
                    'test' => 'Field',
                ],
            ),
        );

        $this->assertEquals(
            expected: new Result(
                properties: [
                    'test' => new StringType(
                        maxLength: 255,
                        format: StringFormat::email,
                        title: 'Field',
                    ),
                ],
                required: [
                    'test',
                ],
            ),
            actual: $provider->get(
                params: new Params(
                    entityType: 'Test',
                    field: 'test',
                    action: Action::Update,
                ),
            ),
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

    /**
     * @param string[] $codes
     */
    private function createCurrencyConfig(string $defaultCode, array $codes = []): CurrencyConfig
    {
        $currencyConfig = $this->createMock(CurrencyConfig::class);

        $currencyConfig->method('getDefaultCurrency')
            ->willReturn($defaultCode);

        $currencyConfig->method('getCurrencyList')
            ->willReturn($codes);

        return $currencyConfig;
    }
}
