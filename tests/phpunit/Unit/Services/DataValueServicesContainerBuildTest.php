<?php

namespace SMW\Tests\Services;

use Onoi\CallbackContainer\CallbackContainerFactory;
use SMW\Services\DataValueServiceFactory;
use SMWPropertyValue as PropertyValue;
use SMW\DataValues\ValueParsers\PropertyValueParser;
use SMW\DataValues\ValueFormatters\PropertyValueFormatter;
use SMW\DataValues\ValueParsers\AllowsPatternValueParser;
use SMW\DataValues\AllowsPatternValue;
use SMW\DataValues\ValueValidators\CompoundConstraintValueValidator;
use SMW\DataValues\ImportValue;
use SMW\DataValues\ValueParsers\ImportValueParser;
use SMW\Settings;

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

		$containerBuilder->registerObject( 'Settings', new Settings( array(
			'smwgPropertyInvalidCharacterList' => array( 'Foo' ) )
		) );

		$containerBuilder->registerObject( 'MediaWikiNsContentReader', $this->mediaWikiNsContentReader );

		$containerBuilder->registerFromFile( $this->servicesFileDir . '/' . 'DataValueServices.php' );

		$this->assertInstanceOf(
			$expected,
			call_user_func_array( array( $containerBuilder, 'create' ), $parameters )
		);
	}

	public function servicesProvider() {

		$provider[] = array(
			DataValueServiceFactory::TYPE_PARSER . PropertyValue::TYPE_ID,
			array(),
			PropertyValueParser::class
		);

		$provider[] = array(
			DataValueServiceFactory::TYPE_FORMATTER . PropertyValue::TYPE_ID,
			array(),
			PropertyValueFormatter::class
		);

		$provider[] = array(
			DataValueServiceFactory::TYPE_PARSER . AllowsPatternValue::TYPE_ID,
			array(),
			AllowsPatternValueParser::class
		);

		$provider[] = array(
			DataValueServiceFactory::TYPE_VALIDATOR . 'CompoundConstraintValueValidator',
			array(),
			CompoundConstraintValueValidator::class
		);

		$provider[] = array(
			DataValueServiceFactory::TYPE_PARSER . ImportValue::TYPE_ID,
			array(),
			ImportValueParser::class
		);

		return $provider;
	}

}
