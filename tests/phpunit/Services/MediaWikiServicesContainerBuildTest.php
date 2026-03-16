<?php

namespace SMW\Tests\Services;

use MediaWiki\Title\Title;
use Onoi\CallbackContainer\CallbackContainerFactory;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Wikimedia\Rdbms\ILoadBalancer;

/**
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 2.5
 *
 * @author mwjames
 */
class MediaWikiServicesContainerBuildTest extends TestCase {

	private $callbackContainerFactory;
	private $servicesFileDir;

	protected function setUp(): void {
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
		$containerBuilder->registerFromFile( $this->servicesFileDir . '/' . 'mediawiki.php' );

		$this->assertInstanceOf(
			$expected,
			call_user_func_array( [ $containerBuilder, 'create' ], $parameters )
		);
	}

	public function servicesProvider() {
		$title = $this->getMockBuilder( Title::class )
			->disableOriginalConstructor()
			->getMock();
		$title->expects( $this->any() )
			->method( 'canExist' )
			->willReturn( true );

		$provider[] = [
			'WikiPage',
			[ $title ],
			'\WikiPage'
		];

		$provider[] = [
			'DBLoadBalancer',
			[],
			ILoadBalancer::class
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
			LoggerInterface::class
		];

		$provider[] = [
			'JobQueueGroup',
			[],
			'\JobQueueGroup'
		];

		$provider[] = [
			'SearchEngineConfig',
			[],
			'\SearchEngineConfig'
		];

		return $provider;
	}
}
