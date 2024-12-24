<?php

namespace SMW\Tests\MediaWiki\Specials\Admin;

use SMW\MediaWiki\Specials\Admin\MaintenanceTaskHandler;
use SMW\Tests\TestEnvironment;
use SMW\Tests\PHPUnitCompat;

/**
 * @covers \SMW\MediaWiki\Specials\Admin\MaintenanceTaskHandler
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.1
 *
 * @author mwjames
 */
class MaintenanceTaskHandlerTest extends \PHPUnit\Framework\TestCase {

	use PHPUnitCompat;

	private $testEnvironment;
	private $store;
	private $outputFormatter;
	private $fileFetcher;

	protected function setUp(): void {
		parent::setUp();

		$this->testEnvironment = new TestEnvironment();

		$this->store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$this->outputFormatter = $this->getMockBuilder( '\SMW\MediaWiki\Specials\Admin\OutputFormatter' )
			->disableOriginalConstructor()
			->getMock();

		$this->fileFetcher = $this->getMockBuilder( '\SMW\Utils\FileFetcher' )
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
			MaintenanceTaskHandler::class,
			new MaintenanceTaskHandler( $this->outputFormatter, $this->fileFetcher, [] )
		);
	}

	public function testGetHtml() {
		$taskHandler = $this->getMockBuilder( '\SMW\MediaWiki\Specials\Admin\TaskHandler' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$this->fileFetcher->expects( $this->once() )
			->method( 'findByExtension' )
			->willReturn( [] );

		$instance = new MaintenanceTaskHandler(
			$this->outputFormatter,
			$this->fileFetcher,
			[
				$taskHandler
			]
		);
		$instance->setFeatureSet( SMW_ADM_MAINTENANCE_SCRIPT_DOCS );

		$this->assertIsString(

			$instance->getHtml()
		);
	}

	public function testIsTaskFor() {
		$taskHandler = $this->getMockBuilder( '\SMW\MediaWiki\Specials\Admin\ActionableTask' )
			->disableOriginalConstructor()
			->getMock();

		$taskHandler->expects( $this->once() )
			->method( 'isTaskFor' )
			->with( 'foo' )
			->willReturn( true );

		$instance = new MaintenanceTaskHandler(
			$this->outputFormatter,
			$this->fileFetcher,
			[
				$taskHandler
			]
		);

		$this->assertTrue(
			$instance->isTaskFor( 'foo' )
		);
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

		$instance = new MaintenanceTaskHandler(
			$this->outputFormatter,
			$this->fileFetcher,
			[
				$taskHandler
			]
		);

		$instance->setStore( $this->store );

		$instance->handleRequest( $webRequest );
	}

}
