<?php

namespace SMW\Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use SMW\ConstraintFactory;
use SMW\DataValues\AllowsListValue;
use SMW\DataValues\AllowsPatternValue;
use SMW\DataValues\MonolingualTextValue;
use SMW\DataValues\Number\UnitConverter;
use SMW\DataValues\NumberValue;
use SMW\DataValues\PropertyValue;
use SMW\DataValues\ReferenceValue;
use SMW\DataValues\StringValue;
use SMW\DataValues\TimeValue;
use SMW\DataValues\ValueFormatters\CodeStringValueFormatter;
use SMW\DataValues\ValueFormatters\MonolingualTextValueFormatter;
use SMW\DataValues\ValueFormatters\NumberValueFormatter;
use SMW\DataValues\ValueFormatters\PropertyValueFormatter;
use SMW\DataValues\ValueFormatters\ReferenceValueFormatter;
use SMW\DataValues\ValueFormatters\StringValueFormatter;
use SMW\DataValues\ValueFormatters\TimeValueFormatter;
use SMW\DataValues\ValueParsers\AllowsListValueParser;
use SMW\DataValues\ValueParsers\AllowsPatternValueParser;
use SMW\DataValues\ValueParsers\MonolingualTextValueParser;
use SMW\DataValues\ValueParsers\PropertyValueParser;
use SMW\DataValues\ValueValidators\CompoundConstraintValueValidator;
use SMW\EntityCache;
use SMW\MediaWiki\MediaWikiNsContentReader;
use SMW\Property\SpecificationLookup;
use SMW\Schema\SchemaFactory;
use SMW\Services\DataValueServiceFactory;
use SMW\Settings;
use SMW\Store;
use SMW\Tests\TestEnvironment;

/**
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 2.5
 *
 * @author mwjames
 */
class DataValueServicesContainerBuildTest extends TestCase {

	private TestEnvironment $testEnvironment;
	private Store $store;
	private $servicesFileDir;
	private $mediaWikiNsContentReader;
	private $propertySpecificationLookup;
	private $schemaFactory;
	private ConstraintFactory $constraintFactory;
	private $entityCache;

	protected function setUp(): void {
		parent::setUp();

		$this->testEnvironment = new TestEnvironment();

		$this->store = $this->getMockBuilder( Store::class )
			->disableOriginalConstructor()
			->getMock();

		$this->mediaWikiNsContentReader = $this->getMockBuilder( MediaWikiNsContentReader::class )
			->disableOriginalConstructor()
			->getMock();

		$this->propertySpecificationLookup = $this->getMockBuilder( SpecificationLookup::class )
			->disableOriginalConstructor()
			->getMock();

		$this->schemaFactory = $this->getMockBuilder( SchemaFactory::class )
			->disableOriginalConstructor()
			->setMethods( null )
			->getMock();

		$this->constraintFactory = $this->getMockBuilder( ConstraintFactory::class )
			->disableOriginalConstructor()
			->setMethods( null )
			->getMock();

		$this->entityCache = $this->getMockBuilder( EntityCache::class )
			->disableOriginalConstructor()
			->getMock();

		$this->servicesFileDir = $GLOBALS['smwgServicesFileDir'];
	}

	protected function tearDown(): void {
		$this->testEnvironment->tearDown();
		parent::tearDown();
	}

	/**
	 * @dataProvider servicesProvider
	 */
	public function testCanConstruct( $service, $parameters, $expected ) {
		$this->testEnvironment->registerObject( 'Settings', new Settings( [
			'smwgPropertyInvalidCharacterList' => [ 'Foo' ] ]
		) );

		$this->testEnvironment->registerObject( 'MediaWikiNsContentReader', $this->mediaWikiNsContentReader );
		$this->testEnvironment->registerObject( 'PropertySpecificationLookup', $this->propertySpecificationLookup );
		$this->testEnvironment->registerObject( 'Store', $this->store );
		$this->testEnvironment->registerObject( 'SchemaFactory', $this->schemaFactory );
		$this->testEnvironment->registerObject( 'ConstraintFactory', $this->constraintFactory );
		$this->testEnvironment->registerObject( 'EntityCache', $this->entityCache );

		$servicesContainer = DataValueServiceFactory::newServicesContainer( $this->servicesFileDir );

		$this->assertInstanceOf(
			$expected,
			$servicesContainer->create( $service, $servicesContainer, ...$parameters )
		);
	}

	public function servicesProvider() {
		$provider[] = [
			DataValueServiceFactory::TYPE_PARSER . PropertyValue::TYPE_ID,
			[],
			PropertyValueParser::class
		];

		$provider[] = [
			DataValueServiceFactory::TYPE_FORMATTER . PropertyValue::TYPE_ID,
			[],
			PropertyValueFormatter::class
		];

		$provider[] = [
			DataValueServiceFactory::TYPE_PARSER . AllowsPatternValue::TYPE_ID,
			[],
			AllowsPatternValueParser::class
		];

		$provider[] = [
			DataValueServiceFactory::TYPE_PARSER . AllowsListValue::TYPE_ID,
			[],
			AllowsListValueParser::class
		];

		$provider[] = [
			DataValueServiceFactory::TYPE_VALIDATOR . 'CompoundConstraintValueValidator',
			[],
			CompoundConstraintValueValidator::class
		];

		$provider[] = [
			DataValueServiceFactory::TYPE_FORMATTER . StringValue::TYPE_ID,
			[],
			StringValueFormatter::class
		];

		$provider[] = [
			DataValueServiceFactory::TYPE_FORMATTER . StringValue::TYPE_COD_ID,
			[],
			CodeStringValueFormatter::class
		];

		$provider[] = [
			DataValueServiceFactory::TYPE_FORMATTER . ReferenceValue::TYPE_ID,
			[],
			ReferenceValueFormatter::class
		];

		$provider[] = [
			DataValueServiceFactory::TYPE_FORMATTER . ReferenceValue::TYPE_ID,
			[],
			ReferenceValueFormatter::class
		];

		$provider[] = [
			DataValueServiceFactory::TYPE_PARSER . MonolingualTextValue::TYPE_ID,
			[],
			MonolingualTextValueParser::class
		];

		$provider[] = [
			DataValueServiceFactory::TYPE_FORMATTER . MonolingualTextValue::TYPE_ID,
			[],
			MonolingualTextValueFormatter::class
		];

		$provider[] = [
			DataValueServiceFactory::TYPE_FORMATTER . NumberValue::TYPE_ID,
			[],
			NumberValueFormatter::class
		];

		$provider[] = [
			DataValueServiceFactory::TYPE_FORMATTER . TimeValue::TYPE_ID,
			[],
			TimeValueFormatter::class
		];

		$provider[] = [
			'UnitConverter',
			[],
			UnitConverter::class
		];

		return $provider;
	}

}
