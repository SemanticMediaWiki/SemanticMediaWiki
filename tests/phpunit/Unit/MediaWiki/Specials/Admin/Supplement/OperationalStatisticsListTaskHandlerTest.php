<?php

namespace SMW\Tests\MediaWiki\Specials\Admin\Supplement;

use SMW\MediaWiki\Specials\Admin\Supplement\OperationalStatisticsListTaskHandler;
use SMW\Tests\TestEnvironment;

/**
 * @covers \SMW\MediaWiki\Specials\Admin\Supplement\OperationalStatisticsListTaskHandler
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class OperationalStatisticsListTaskHandlerTest extends \PHPUnit_Framework_TestCase {

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
			OperationalStatisticsListTaskHandler::class,
			new OperationalStatisticsListTaskHandler( $this->outputFormatter )
		);
	}

	public function testGetHtml() {

		$instance = new OperationalStatisticsListTaskHandler(
			$this->outputFormatter
		);

		$this->assertInternalType(
			'string',
			$instance->getHtml()
		);
	}

	public function testIsTaskFor() {

		$instance = new OperationalStatisticsListTaskHandler(
			$this->outputFormatter
		);

		$this->assertTrue(
			$instance->isTaskFor( 'stats')
		);
	}

	public function testHandleRequest() {

		$semanticStatistics = [
			'PROPUSES' => 0,
			'ERRORUSES' => 0,
			'USEDPROPS' => 0,
			'TOTALPROPS' => 0,
			'OWNPAGE' => 0,
			'DECLPROPS' => 0,
			'DELETECOUNT' => 0,
			'SUBOBJECTS' => 0,
			'QUERY' => 0,
			'CONCEPTS' => 0
		];

		$this->store->expects( $this->once() )
			->method( 'getStatistics' )
			->will( $this->returnValue( $semanticStatistics ) );

		$this->outputFormatter->expects( $this->atLeastOnce() )
			->method( 'addHtml' );

		$instance = new OperationalStatisticsListTaskHandler(
			$this->outputFormatter
		);

		$instance->setStore( $this->store );

		$webRequest = $this->getMockBuilder( '\WebRequest' )
			->disableOriginalConstructor()
			->getMock();

		$instance->handleRequest( $webRequest );
	}

	public function testHandleSubRequest() {

		$webRequest = $this->getMockBuilder( '\WebRequest' )
			->disableOriginalConstructor()
			->getMock();

		$taskHandler = $this->getMockBuilder( '\SMW\MediaWiki\Specials\Admin\TaskHandler' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$taskHandler->expects( $this->once() )
			->method( 'isTaskFor' )
			->will( $this->returnValue( true ) );

		$taskHandler->expects( $this->once() )
			->method( 'handleRequest' )
			->with( $this->equalTo( $webRequest ) );

		$instance = new OperationalStatisticsListTaskHandler(
			$this->outputFormatter,
			[ $taskHandler ]
		);

		$instance->setStore( $this->store );

		$instance->handleRequest( $webRequest );
	}

}
