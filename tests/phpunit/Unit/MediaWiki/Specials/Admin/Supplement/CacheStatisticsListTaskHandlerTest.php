<?php

namespace SMW\Tests\MediaWiki\Specials\Admin\Supplement;

use SMW\MediaWiki\Specials\Admin\Supplement\CacheStatisticsListTaskHandler;
use SMW\Tests\TestEnvironment;

/**
 * @covers \SMW\MediaWiki\Specials\Admin\Supplement\CacheStatisticsListTaskHandler
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class CacheStatisticsListTaskHandlerTest extends \PHPUnit_Framework_TestCase {

	private $testEnvironment;
	private $store;
	private $outputFormatter;

	protected function setUp() {
		parent::setUp();

		$this->testEnvironment = new TestEnvironment();

		$this->store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$this->outputFormatter = $this->getMockBuilder( '\SMW\MediaWiki\Specials\Admin\OutputFormatter' )
			->disableOriginalConstructor()
			->getMock();

		$this->testEnvironment->registerObject( 'Store', $this->store );
	}

	protected function tearDown() {
		$this->testEnvironment->tearDown();
		parent::tearDown();
	}

	public function testCanConstruct() {

		$this->assertInstanceOf(
			CacheStatisticsListTaskHandler::class,
			new CacheStatisticsListTaskHandler( $this->outputFormatter )
		);
	}

	public function testGetHtml() {

		$instance = new CacheStatisticsListTaskHandler(
			$this->outputFormatter
		);

		$this->assertInternalType(
			'string',
			$instance->getHtml()
		);
	}

	public function testIsTaskFor() {

		$instance = new CacheStatisticsListTaskHandler(
			$this->outputFormatter
		);

		$this->assertTrue(
			$instance->isTaskFor( 'stats/cache')
		);
	}

	public function testHandleRequest() {

		$this->outputFormatter->expects( $this->atLeastOnce() )
			->method( 'addHtml' );

		$instance = new CacheStatisticsListTaskHandler(
			$this->outputFormatter
		);

		$webRequest = $this->getMockBuilder( '\WebRequest' )
			->disableOriginalConstructor()
			->getMock();

		$instance->handleRequest( $webRequest );
	}


}
