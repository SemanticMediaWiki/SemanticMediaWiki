<?php

namespace SMW\Tests\MediaWiki\Specials\Admin\Supplement;

use SMW\MediaWiki\Specials\Admin\Supplement\OperationalStatisticsListTaskHandler;
use SMW\Tests\TestEnvironment;
use SMW\Tests\PHPUnitCompat;

/**
 * @covers \SMW\MediaWiki\Specials\Admin\Supplement\OperationalStatisticsListTaskHandler
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class OperationalStatisticsListTaskHandlerTest extends \PHPUnit\Framework\TestCase {

	use PHPUnitCompat;

	private $testEnvironment;
	private $store;
	private $outputFormatter;

	protected function setUp(): void {
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

	protected function tearDown(): void {
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

		$this->assertIsString(

			$instance->getHtml()
		);
	}

	public function testIsTaskFor() {
		$instance = new OperationalStatisticsListTaskHandler(
			$this->outputFormatter
		);

		$this->assertTrue(
			$instance->isTaskFor( 'stats' )
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
			->willReturn( $semanticStatistics );

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

		$webRequest->expects( $this->once() )
			->method( 'getText' )
			->with( 'action' )
			->willReturn( 'foo' );

		$taskHandler = $this->getMockBuilder( '\SMW\MediaWiki\Specials\Admin\ActionableTask' )
			->disableOriginalConstructor()
			->getMock();

		$taskHandler->expects( $this->once() )
			->method( 'isTaskFor' )
			->willReturn( true );

		$taskHandler->expects( $this->once() )
			->method( 'handleRequest' )
			->with( $webRequest );

		$instance = new OperationalStatisticsListTaskHandler(
			$this->outputFormatter,
			[ $taskHandler ]
		);

		$instance->setStore( $this->store );

		$instance->handleRequest( $webRequest );
	}

}
