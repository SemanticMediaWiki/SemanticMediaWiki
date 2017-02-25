<?php

namespace SMW\Tests\Services;

use Onoi\CallbackContainer\CallbackContainerFactory;
use SMW\Services\DataValueServiceFactory;
use SMWPropertyValue as PropertyValue;
use SMW\DataValues\ValueParsers\PropertyValueParser;
use SMW\DataValues\ValueFormatters\PropertyValueFormatter;
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

	protected function setUp() {
		parent::setUp();

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

		return $provider;
	}

}
