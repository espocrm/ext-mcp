<?php
/**LICENSE**/

namespace tests\unit\Espo\Modules\Mcp\Tools\Schema\Field;

use Espo\Core\Acl;
use Espo\Core\Currency\ConfigDataProvider as CurrencyConfig;
use Espo\Core\ORM\Type\FieldType;
use Espo\Core\Utils\Config;
use Espo\Core\Utils\Language;
use Espo\Modules\Mcp\Tools\JsonSchema\ConstSchema;
use Espo\Modules\Mcp\Tools\JsonSchema\EnumSchema;
use Espo\Modules\Mcp\Tools\JsonSchema\GroupKeyword;
use Espo\Modules\Mcp\Tools\JsonSchema\GroupSchema;
use Espo\Modules\Mcp\Tools\JsonSchema\StringFormat;
use Espo\Modules\Mcp\Tools\JsonSchema\Type\ArrayType;
use Espo\Modules\Mcp\Tools\JsonSchema\Type\BooleanType;
use Espo\Modules\Mcp\Tools\JsonSchema\Type\IntegerType;
use Espo\Modules\Mcp\Tools\JsonSchema\Type\NullType;
use Espo\Modules\Mcp\Tools\JsonSchema\Type\NumberType;
use Espo\Modules\Mcp\Tools\JsonSchema\Type\ObjectType;
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
use Espo\Modules\Mcp\Tools\Schema\Field\Types\LinkMultipleSchemaProvider;
use Espo\Modules\Mcp\Tools\Schema\Field\Types\LinkParentSchemaProvider;
use Espo\Modules\Mcp\Tools\Schema\Field\Types\LinkSchemaProvider;
use Espo\Modules\Mcp\Tools\Schema\Field\Types\PersonNameSchemaProvider;
use Espo\Modules\Mcp\Tools\Schema\Field\Types\PhoneSchemaProvider;
use Espo\Modules\Mcp\Tools\Schema\Field\Types\TextSchemaProvider;
use Espo\Modules\Mcp\Tools\Schema\Field\Types\UrlSchemaProvider;
use Espo\Modules\Mcp\Tools\Schema\Field\Types\VarcharSchemaProvider;
use Espo\Modules\Mcp\Tools\Schema\Field\Types\WysiwygSchemaProvider;
use Espo\Modules\Mcp\Tools\Schema\Field\Util;
use Espo\Modules\Mcp\Tools\Schema\Util\EnumOptionsProvider;
use Espo\Modules\Mcp\Tools\Schema\Util\EnumOptionTranslator;
use Espo\ORM\Defs;
use Espo\ORM\Defs\EntityDefs;
use Espo\ORM\Type\AttributeType;
use Espo\ORM\Type\RelationType;
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

    public function testLinkMultipleWrite(): void
    {
        $acl = $this->createMock(Acl::class);

        $acl->expects(self::once())
            ->method('checkScope')
            ->with('Another')
            ->willReturn(true);

        $provider = new LinkMultipleSchemaProvider(
            ormDefs: $this->createOrmDefsMulti([
                self::createEntityDefsCommon(
                    entityType: 'Test',
                    defs: [
                        'fields' => [
                            'test' => [
                                'type' => FieldType::LINK_MULTIPLE,
                                'required' => true,
                            ],
                        ],
                        'attributes' => [
                            'testIds' => [
                                'type' => AttributeType::JSON_ARRAY,
                            ],
                        ],
                        'relations' => [
                            'test' => [
                                'type' => RelationType::HAS_MANY,
                                'entity' => 'Another',
                            ],
                        ],
                    ],
                ),
            ]),
            defaultLanguage: $this->createLanguage(
                fields: [
                    'test' => 'Field',
                ],
                scopeNames: [
                    'Another' => 'Another Label'
                ]
            ),
            acl: $acl,
        );

        $this->assertEquals(
            expected: new Result(
                properties: [
                    'testIds' => new ArrayType(
                        items: new StringType(
                            description: "'Another' record ID.",
                        ),
                        minItems: 1,
                        title: 'Field (IDs)',
                        description:
                        "An IDs attribute of the 'Field' link-multiple field. Field name: `test`. " .
                        "Specifies the 'Another Label' record IDs. Foreign type: `Another`. " .
                        "Tool to retrieve IDs: `Find.Another`."
                    ),
                ],
                required: [
                    'testIds',
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

    public function testLinkMultipleRead(): void
    {
        $acl = $this->createMock(Acl::class);

        $provider = new LinkMultipleSchemaProvider(
            ormDefs: $this->createOrmDefsMulti([
                self::createEntityDefsCommon(
                    entityType: 'Test',
                    defs: [
                        'fields' => [
                            'test' => [
                                'type' => FieldType::LINK_MULTIPLE,
                                'required' => true,
                            ],
                        ],
                        'attributes' => [
                            'testIds' => [
                                'type' => AttributeType::JSON_ARRAY,
                            ],
                        ],
                        'relations' => [
                            'test' => [
                                'type' => RelationType::HAS_MANY,
                                'entity' => 'Another',
                            ],
                        ],
                    ],
                ),
            ]),
            defaultLanguage: $this->createLanguage(
                fields: [
                    'test' => 'Field',
                ],
                scopeNames: [
                    'Another' => 'Another Label'
                ]
            ),
            acl: $acl,
        );

        $this->assertEquals(
            expected: new Result(
                properties: [
                    'testIds' => new ArrayType(
                        items: new StringType(
                            description: "'Another' record ID.",
                        ),
                        minItems: 1,
                        title: 'Field (IDs)',
                        description:
                            "An IDs attribute of the 'Field' link-multiple field. Field name: `test`. " .
                            "Specifies the 'Another Label' record IDs. Foreign type: `Another`. " .
                            "Tool to retrieve IDs: `Find.Another`."
                    ),
                    'testNames' => new ObjectType(
                        additionalProperties: Util::wrapWithNull(
                            new StringType(
                                description: "Record name.",
                            )
                        ),
                        description:
                            "Names attribute of 'Field' link-multiple field. Field name: `test`. " .
                            "Contains the mapping of IDs to record names.",
                    ),
                ],
                required: [
                    'testIds',
                ],
            ),
            actual: $provider->get(
                params: new Params(
                    entityType: 'Test',
                    field: 'test',
                    action: Action::Read,
                ),
            ),
        );
    }

    public function testLinkParentWrite(): void
    {
        $acl = $this->createMock(Acl::class);

        $acl->method('checkScope')
            ->willReturnCallback(function ($scope) {
                if ($scope === 'C') {
                    return false;
                }

                return true;
            });

        $provider = new LinkParentSchemaProvider(
            ormDefs: $this->createOrmDefsMulti([
                self::createEntityDefsCommon(
                    entityType: 'Test',
                    defs: [
                        'fields' => [
                            'parent' => [
                                'type' => FieldType::LINK_PARENT,
                                'required' => true,
                                'entityList' => ['A', 'B', 'C'],
                            ],
                        ],
                        'attributes' => [
                            'parentId' => [
                                'type' => AttributeType::FOREIGN_ID,
                            ],
                        ],
                    ],
                ),
            ]),
            defaultLanguage: $this->createLanguage(
                fields: [
                    'parent' => 'Parent',
                ],
                scopeNames: [
                    'A' => 'A Label',
                    'B' => 'B Label',
                ],
            ),
            acl: $acl,
        );

        $this->assertEquals(
            expected: new Result(
                properties: [
                    'parentId' => new StringType(
                        title: "Parent (ID)",
                        description:
                            "An ID attribute of the 'Parent' link-parent (polymorphic) field. " .
                            "Field name: `parent`. " .
                            "Specifies the foreign record ID." .
                            "Tool to retrieve IDs: `Find.{entityType}`."
                    ),
                    'parentType' => new GroupSchema(
                        keyword: GroupKeyword::anyOf,
                        schemas: [
                            new ConstSchema(
                                value: 'A',
                                title: 'A Label',
                            ),
                            new ConstSchema(
                                value: 'B',
                                title: 'B Label',
                            ),
                        ],
                        title: "Parent (Type)",
                        description:
                            "A Type attribute of the 'Parent' link-parent (polymorphic) field. " .
                            "Field name: `parent`. " .
                            "Specifies the foreign entity type.",
                    ),
                ],
                required: [
                    'parentId',
                    'parentType',
                ],
            ),
            actual: $provider->get(
                params: new Params(
                    entityType: 'Test',
                    field: 'parent',
                    action: Action::Update,
                ),
            ),
        );
    }

    public function testLinkParentRead(): void
    {
        $acl = $this->createMock(Acl::class);

        $acl->method('checkScope')
            ->willReturnCallback(function () {
                return true;
            });

        $provider = new LinkParentSchemaProvider(
            ormDefs: $this->createOrmDefsMulti([
                self::createEntityDefsCommon(
                    entityType: 'Test',
                    defs: [
                        'fields' => [
                            'parent' => [
                                'type' => FieldType::LINK_PARENT,
                                'required' => true,
                                'entityList' => ['A'],
                            ],
                        ],
                        'attributes' => [
                            'parentId' => [
                                'type' => AttributeType::FOREIGN_ID,
                            ],
                        ],
                    ],
                ),
            ]),
            defaultLanguage: $this->createLanguage(
                fields: [
                    'parent' => 'Parent',
                ],
                scopeNames: [
                    'A' => 'A Label',
                ],
            ),
            acl: $acl,
        );

        $this->assertEquals(
            expected: new Result(
                properties: [
                    'parentId' => new StringType(
                        title: "Parent (ID)",
                        description:
                        "An ID attribute of the 'Parent' link-parent (polymorphic) field. " .
                        "Field name: `parent`. " .
                        "Specifies the foreign record ID." .
                        "Tool to retrieve IDs: `Find.{entityType}`."
                    ),
                    'parentType' => new GroupSchema(
                        keyword: GroupKeyword::anyOf,
                        schemas: [
                            new ConstSchema(
                                value: 'A',
                                title: 'A Label',
                            ),
                        ],
                        title: "Parent (Type)",
                        description:
                            "A Type attribute of the 'Parent' link-parent (polymorphic) field. " .
                            "Field name: `parent`. " .
                            "Specifies the foreign entity type.",
                    ),
                    'parentName' => new UnionTypeSchema(
                        schemas: [
                            new StringType(
                                title: 'Parent (Name)',
                                description:
                                    "A Name attribute of 'Parent' link-parent field. Field name: `parent`. " .
                                    "Contains the related record name.",
                            ),
                            new NullType(),
                        ]
                    )
                ],
                required: [
                    'parentId',
                    'parentType',
                ],
            ),
            actual: $provider->get(
                params: new Params(
                    entityType: 'Test',
                    field: 'parent',
                    action: Action::Read,
                ),
            ),
        );
    }

    public function testLinkWrite(): void
    {
        $acl = $this->createMock(Acl::class);

        $acl->method('checkScope')
            ->willReturnCallback(function ($scope) {
                if ($scope === 'A') {
                    return true;
                }

                return false;
            });

        $provider = new LinkSchemaProvider(
            ormDefs: $this->createOrmDefsMulti([
                self::createEntityDefsCommon(
                    entityType: 'Test',
                    defs: [
                        'fields' => [
                            'field' => [
                                'type' => FieldType::LINK,
                                'required' => true,
                            ],
                        ],
                        'attributes' => [
                            'fieldId' => [
                                'type' => AttributeType::FOREIGN_ID,
                            ],
                        ],
                        'relations' => [
                            'field' => [
                                'type' => RelationType::BELONGS_TO,
                                'entity' => 'A',
                            ]
                        ],
                    ],
                ),
            ]),
            defaultLanguage: $this->createLanguage(
                fields: [
                    'field' => 'Field',
                ],
                scopeNames: [
                    'A' => 'A Label',
                ],
            ),
            acl: $acl,
        );

        $this->assertEquals(
            expected: new Result(
                properties: [
                    'fieldId' => new StringType(
                        title: "Field (ID)",
                        description:
                            "An ID attribute of the 'Field' link field. Field name: `field`. " .
                            "Specifies the 'A Label' record ID. Foreign type: `A`. " .
                            "Tool to retrieve IDs: `Find.A`."
                    ),
                ],
                required: [
                    'fieldId',
                ],
            ),
            actual: $provider->get(
                params: new Params(
                    entityType: 'Test',
                    field: 'field',
                    action: Action::Update,
                ),
            ),
        );
    }

    public function testLinkRead(): void
    {
        $acl = $this->createMock(Acl::class);

        $acl->method('checkScope')
            ->willReturnCallback(function ($scope) {
                if ($scope === 'A') {
                    return true;
                }

                return false;
            });

        $provider = new LinkSchemaProvider(
            ormDefs: $this->createOrmDefsMulti([
                self::createEntityDefsCommon(
                    entityType: 'Test',
                    defs: [
                        'fields' => [
                            'field' => [
                                'type' => FieldType::LINK,
                                'required' => true,
                            ],
                        ],
                        'attributes' => [
                            'fieldId' => [
                                'type' => AttributeType::FOREIGN_ID,
                            ],
                        ],
                        'relations' => [
                            'field' => [
                                'type' => RelationType::BELONGS_TO,
                                'entity' => 'A',
                            ]
                        ],
                    ],
                ),
            ]),
            defaultLanguage: $this->createLanguage(
                fields: [
                    'field' => 'Field',
                ],
                scopeNames: [
                    'A' => 'A Label',
                ],
            ),
            acl: $acl,
        );

        $this->assertEquals(
            expected: new Result(
                properties: [
                    'fieldId' => new StringType(
                        title: "Field (ID)",
                        description:
                            "An ID attribute of the 'Field' link field. Field name: `field`. " .
                            "Specifies the 'A Label' record ID. Foreign type: `A`. " .
                            "Tool to retrieve IDs: `Find.A`."
                    ),
                    'fieldName' => new UnionTypeSchema(
                        schemas: [
                            new StringType(
                                title: "Field (Name)",
                                description:
                                    "A Name attribute of 'Field' link field. Field name: `field`. " .
                                    "Contains the related record name.",
                            ),
                            new NullType(),
                        ]
                    )
                ],
                required: [
                    'fieldId',
                ],
            ),
            actual: $provider->get(
                params: new Params(
                    entityType: 'Test',
                    field: 'field',
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

    public function testPhone(): void
    {
        $config = $this->createMock(Config::class);

        $provider = new PhoneSchemaProvider(
            ormDefs: $this->createOrmDefs(
                entityDefs: self::createEntityDefs(
                    entityType: 'Test',
                    field: 'phoneNumber',
                    type: FieldType::PHONE,
                    required: true,
                ),
            ),
            defaultLanguage: $this->createLanguage(
                fields: [
                    'phoneNumber' => 'Phone',
                ],
            ),
            config: $config,
        );

        $this->assertEquals(
            expected: new Result(
                properties: [
                    'phoneNumber' => new StringType(
                        maxLength: 36,
                        title: 'Phone',
                        description: 'A phone number.',
                    ),
                ],
                required: [
                    'phoneNumber',
                ],
            ),
            actual: $provider->get(
                params: new Params(
                    entityType: 'Test',
                    field: 'phoneNumber',
                    action: Action::Update,
                ),
            ),
        );
    }

    public function testPersonName(): void
    {
        $provider = new PersonNameSchemaProvider(
            ormDefs: $this->createOrmDefsMulti([
                self::createEntityDefsCommon(
                    entityType: 'Test',
                    defs: [
                        'fields' => [
                            'name' => [
                                'type' => FieldType::PERSON_NAME,
                            ],
                            'lastName' => [
                                'type' => FieldType::VARCHAR,
                                'required' => true,
                            ],
                        ],
                    ],
                ),
            ]),
            defaultLanguage: $this->createLanguage(
                fields: [
                    'name' => 'Name',
                ],
            ),
        );

        $this->assertEquals(
            expected: new Result(
                properties: [
                    'name' => new StringType(
                        title: 'Name',
                        description: 'Person name.',
                    ),
                ],
            ),
            actual: $provider->get(
                params: new Params(
                    entityType: 'Test',
                    field: 'name',
                    action: Action::Read,
                ),
            ),
        );
    }

    public function testText(): void
    {
        $provider = new TextSchemaProvider(
            ormDefs: $this->createOrmDefs(
                entityDefs: self::createEntityDefs(
                    entityType: 'Test',
                    field: 'test',
                    type: FieldType::TEXT,
                    required: true,
                ),
            ),
            defaultLanguage: $this->createLanguage(
                fields: [
                    'test' => 'Test',
                ],
            ),
        );

        $this->assertEquals(
            expected: new Result(
                properties: [
                    'test' => new StringType(
                        title: 'Test',
                        description: "Multi-line string. Markdown is supported.",
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

    public function testWysiwyg(): void
    {
        $provider = new WysiwygSchemaProvider(
            ormDefs: $this->createOrmDefs(
                entityDefs: self::createEntityDefs(
                    entityType: 'Test',
                    field: 'test',
                    type: FieldType::WYSIWYG,
                    required: true,
                ),
            ),
            defaultLanguage: $this->createLanguage(
                fields: [
                    'test' => 'Test',
                ],
            ),
        );

        $this->assertEquals(
            expected: new Result(
                properties: [
                    'test' => new StringType(
                        title: 'Test',
                        description: "HTML code. Only BODY contents. Only inline styles. No scripts.",
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

    public function testUrl(): void
    {
        $provider = new UrlSchemaProvider(
            ormDefs: $this->createOrmDefs(
                entityDefs: self::createEntityDefs(
                    entityType: 'Test',
                    field: 'test',
                    type: FieldType::URL,
                    required: true,
                    params: [
                        'protocolRequired' => true,
                    ],
                ),
            ),
            defaultLanguage: $this->createLanguage(
                fields: [
                    'test' => 'Test',
                ],
            ),
        );

        $this->assertEquals(
            expected: new Result(
                properties: [
                    'test' => new StringType(
                        maxLength: 255,
                        format: StringFormat::uri,
                        title: 'Test',
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

    public function testVarcharWithoutOptions(): void
    {
        $entityDefs = self::createEntityDefs(
            entityType: 'Test',
            field: 'test',
            type: FieldType::VARCHAR,
            required: true,
            params: [
                'maxLength' => 100,
            ],
        );

        $provider = new VarcharSchemaProvider(
            ormDefs: $this->createOrmDefs(
                entityDefs: $entityDefs,
            ),
            defaultLanguage: $this->createLanguage(
                fields: [
                    'test' => 'Test',
                ],
            ),
            enumOptionsProvider: $this->createMock(EnumOptionsProvider::class),
        );

        $this->assertEquals(
            expected: new Result(
                properties: [
                    'test' =>  new StringType(
                        maxLength: 100,
                        title: 'Test',
                        description: "Single-line.",
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

    public function testVarcharWithOptions(): void
    {
        $entityDefs = self::createEntityDefs(
            entityType: 'Test',
            field: 'test',
            type: FieldType::VARCHAR,
            required: true,
            params: [
                'maxLength' => 100,
            ],
        );

        $enumOptionsProvider = $this->createEnumOptionsProvider($entityDefs->getField('test'), ['A', 'B']);

        $provider = new VarcharSchemaProvider(
            ormDefs: $this->createOrmDefs(
                entityDefs: $entityDefs,
            ),
            defaultLanguage: $this->createLanguage(
                fields: [
                    'test' => 'Test',
                ],
            ),
            enumOptionsProvider: $enumOptionsProvider,
        );

        $this->assertEquals(
            expected: new Result(
                properties: [
                    'test' => GroupSchema::createAnyOf(
                        schemas: [
                            new StringType(
                                maxLength: 100,
                            ),
                            new EnumSchema(
                                values: ['A', 'B'],
                            ),
                        ],
                        title: 'Test',
                        description: "Single-line.",
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
     * @param array<string, string> $scopeNames
     */
    private function createLanguage(array $fields = [], array $options = [], array $scopeNames = []): Language
    {
        $language = $this->createMock(Language::class);

        $language->method('translateLabel')
            ->willReturnCallback(function (string $name, string $category) use ($fields, $scopeNames) {
                if ($category === 'fields') {
                    return $fields[$name] ?? $name;
                }

                if ($category === 'scopeNames') {
                    return $scopeNames[$name] ?? $name;
                }

                return $name;
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
     * @param EntityDefs[] $entityDefsList
     */
    private function createOrmDefsMulti(array $entityDefsList): Defs
    {
        $defs = $this->createMock(Defs::class);

        $map = [];

        foreach ($entityDefsList as $it) {
            $map[] = [$it->getName(), $it];
        }

        $defs->method('getEntity')
            ->willReturnMap($map);

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
     * @param array<string, mixed> $defs
     */
    private static function createEntityDefsCommon(
        string $entityType,
        array $defs,
    ): EntityDefs {

        return EntityDefs::fromRaw(
            raw: $defs,
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
