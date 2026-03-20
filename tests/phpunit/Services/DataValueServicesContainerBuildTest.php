<?php

namespace SMW\Tests\Services;

use Onoi\CallbackContainer\CallbackContainerFactory;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
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

/**
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 2.5
 *
 * @author mwjames
 */
class DataValueServicesContainerBuildTest extends TestCase {

	private Store $store;
	private $callbackContainerFactory;
	private $servicesFileDir;
	private $mediaWikiNsContentReader;
	private $propertySpecificationLookup;
	private $logger;
	private $schemaFactory;
	private ConstraintFactory $constraintFactory;
	private $entityCache;

	protected function setUp(): void {
		parent::setUp();

		$this->store = $this->getMockBuilder( Store::class )
			->disableOriginalConstructor()
			->getMock();

		$this->mediaWikiNsContentReader = $this->getMockBuilder( MediaWikiNsContentReader::class )
			->disableOriginalConstructor()
			->getMock();

		$this->propertySpecificationLookup = $this->getMockBuilder( SpecificationLookup::class )
			->disableOriginalConstructor()
			->getMock();

		$this->logger = $this->getMockBuilder( LoggerInterface::class )
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
		$containerBuilder->registerObject( 'SchemaFactory', $this->schemaFactory );
		$containerBuilder->registerObject( 'ConstraintFactory', $this->constraintFactory );
		$containerBuilder->registerObject( 'EntityCache', $this->entityCache );

		$containerBuilder->registerFromFile( $this->servicesFileDir . '/' . 'datavalues.php' );

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

		$provider[] = [
			'UnitConverter',
			[],
			UnitConverter::class
		];

		return $provider;
	}

}
