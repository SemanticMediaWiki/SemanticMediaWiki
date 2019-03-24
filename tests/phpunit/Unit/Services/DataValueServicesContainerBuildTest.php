<?php

namespace SMW\Tests\Services;

use Onoi\CallbackContainer\CallbackContainerFactory;
use SMW\DataValues\AllowsListValue;
use SMW\DataValues\AllowsPatternValue;
use SMW\DataValues\MonolingualTextValue;
use SMW\DataValues\ReferenceValue;
use SMW\DataValues\StringValue;
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
use SMW\Services\DataValueServiceFactory;
use SMW\Settings;
use SMWNumberValue as NumberValue;
use SMWPropertyValue as PropertyValue;
use SMWTimeValue as TimeValue;

/**
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class DataValueServicesContainerBuildTest extends \PHPUnit_Framework_TestCase {

	private $callbackContainerFactory;
	private $servicesFileDir;
	private $mediaWikiNsContentReader;
	private $propertySpecificationLookup;
	private $logger;
	private $schemaFactory;
	private $entityCache;

	protected function setUp() {
		parent::setUp();

		$this->store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMock();

		$this->mediaWikiNsContentReader = $this->getMockBuilder( '\SMW\MediaWiki\MediaWikiNsContentReader' )
			->disableOriginalConstructor()
			->getMock();

		$this->propertySpecificationLookup = $this->getMockBuilder( '\SMW\PropertySpecificationLookup' )
			->disableOriginalConstructor()
			->getMock();

		$this->logger = $this->getMockBuilder( '\Psr\Log\LoggerInterface' )
			->disableOriginalConstructor()
			->getMock();

		$this->schemaFactory = $this->getMockBuilder( '\SMW\Schema\SchemaFactory' )
			->disableOriginalConstructor()
			->setMethods( null )
			->getMock();

		$this->constraintFactory = $this->getMockBuilder( '\SMW\Property\ConstraintFactory' )
			->disableOriginalConstructor()
			->setMethods( null )
			->getMock();

		$this->entityCache = $this->getMockBuilder( '\SMW\EntityCache' )
			->disableOriginalConstructor()
			->getMock();

		$this->callbackContainerFactory = new CallbackContainerFactory();
		$this->servicesFileDir = $GLOBALS['smwgServicesFileDir'];
	}

	/**
	 * @dataProvider servicesProvider
	 */
	public function testCanConstruct( $service, $parameters, $expected ) {

		array_unshift( $parameters, $service );

		$containerBuilder = $this->callbackContainerFactory->newCallbackContainerBuilder();

		$containerBuilder->registerObject( 'Settings', new Settings( [
			'smwgPropertyInvalidCharacterList' => [ 'Foo' ] ]
		) );

		$containerBuilder->registerObject( 'MediaWikiNsContentReader', $this->mediaWikiNsContentReader );
		$containerBuilder->registerObject( 'PropertySpecificationLookup', $this->propertySpecificationLookup );
		$containerBuilder->registerObject( 'Store', $this->store );
		$containerBuilder->registerObject( 'MediaWikiLogger', $this->logger );
		$containerBuilder->registerObject( 'SchemaFactory', $this->schemaFactory  );
		$containerBuilder->registerObject( 'ConstraintFactory', $this->constraintFactory  );
		$containerBuilder->registerObject( 'EntityCache', $this->entityCache );

		$containerBuilder->registerFromFile( $this->servicesFileDir . '/' . 'DataValueServices.php' );

		$this->assertInstanceOf(
			$expected,
			call_user_func_array( [ $containerBuilder, 'create' ], $parameters )
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

		$provider[] = array(
			'UnitConverter',
			[],
			'\SMW\DataValues\Number\UnitConverter'
		);

		return $provider;
	}

}
