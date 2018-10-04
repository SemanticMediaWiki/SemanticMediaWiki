<?php

namespace SMW\Tests\Services;

use Onoi\CallbackContainer\CallbackContainerFactory;

/**
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class MediaWikiServicesContainerBuildTest extends \PHPUnit_Framework_TestCase {

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
		$containerBuilder->registerFromFile( $this->servicesFileDir . '/' . 'MediaWikiServices.php' );

		$this->assertInstanceOf(
			$expected,
			call_user_func_array( [ $containerBuilder, 'create' ], $parameters )
		);
	}

	public function servicesProvider() {

		$title = $this->getMockBuilder( '\Title' )
			->disableOriginalConstructor()
			->getMock();

		$provider[] = [
			'WikiPage',
			[ $title ],
			'\WikiPage'
		];

		$provider[] = [
			'DBLoadBalancer',
			[],
			'\LoadBalancer'
		];

/*
		$database = $this->getMockBuilder( '\DatabaeBase' )
			->disableOriginalConstructor()
			->getMock();

		$provider[] = array(
			'DefaultSearchEngineTypeForDB',
			array( $database ),
			'\SearchEngine'
		);
*/

		$provider[] = [
			'MediaWikiLogger',
			[],
			'\Psr\Log\LoggerInterface'
		];

		$provider[] = [
			'JobQueueGroup',
			[],
			'\JobQueueGroup'
		];

		return $provider;
	}
}
