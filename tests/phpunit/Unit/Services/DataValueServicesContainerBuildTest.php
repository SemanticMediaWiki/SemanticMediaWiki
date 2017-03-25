<?php

namespace SMW\Tests\Services;

use Onoi\CallbackContainer\CallbackContainerFactory;
use SMW\Services\DataValueServiceFactory;
use SMWPropertyValue as PropertyValue;
use SMW\DataValues\ValueParsers\PropertyValueParser;
use SMW\DataValues\ValueFormatters\PropertyValueFormatter;
use SMW\DataValues\ValueParsers\AllowsPatternValueParser;
use SMW\DataValues\AllowsPatternValue;
use SMW\DataValues\ValueParsers\AllowsListValueParser;
use SMW\DataValues\AllowsListValue;
use SMW\DataValues\ValueValidators\CompoundConstraintValueValidator;
use SMW\DataValues\ImportValue;
use SMW\DataValues\ValueParsers\ImportValueParser;
use SMW\Settings;
use SMWStringValue as StringValue;
use SMW\DataValues\ValueFormatters\StringValueFormatter;
use SMW\DataValues\ValueFormatters\CodeStringValueFormatter;
use SMW\DataValues\ValueFormatters\ReferenceValueFormatter;
use SMW\DataValues\ReferenceValue;
use SMW\DataValues\ValueFormatters\MonolingualTextValueFormatter;
use SMW\DataValues\MonolingualTextValue;
use SMW\DataValues\ValueParsers\MonolingualTextValueParser;
use SMWNumberValue as NumberValue;
use SMW\DataValues\ValueFormatters\NumberValueFormatter;
use SMWTimeValue as TimeValue;
use SMW\DataValues\ValueFormatters\TimeValueFormatter;

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

	protected function setUp() {
		parent::setUp();

		$this->mediaWikiNsContentReader = $this->getMockBuilder( '\SMW\MediaWiki\MediaWikiNsContentReader' )
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

		return $provider;
	}

}
