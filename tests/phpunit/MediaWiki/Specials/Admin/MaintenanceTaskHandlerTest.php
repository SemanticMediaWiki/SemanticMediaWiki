<?php

namespace SMW\Tests\MediaWiki\Specials\Admin;

use MediaWiki\Request\WebRequest;
use PHPUnit\Framework\TestCase;
use SMW\MediaWiki\Specials\Admin\ActionableTask;
use SMW\MediaWiki\Specials\Admin\MaintenanceTaskHandler;
use SMW\MediaWiki\Specials\Admin\OutputFormatter;
use SMW\MediaWiki\Specials\Admin\TaskHandler;
use SMW\Store;
use SMW\Tests\TestEnvironment;
use SMW\Utils\FileFetcher;

/**
 * @covers \SMW\MediaWiki\Specials\Admin\MaintenanceTaskHandler
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 3.1
 *
 * @author mwjames
 */
class MaintenanceTaskHandlerTest extends TestCase {

	private $testEnvironment;
	private $store;
	private $outputFormatter;
	private $fileFetcher;

	protected function setUp(): void {
		parent::setUp();

		$this->testEnvironment = new TestEnvironment();

		$this->store = $this->getMockBuilder( Store::class )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$this->outputFormatter = $this->getMockBuilder( OutputFormatter::class )
			->disableOriginalConstructor()
			->getMock();

		$this->fileFetcher = $this->getMockBuilder( FileFetcher::class )
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
		$taskHandler = $this->getMockBuilder( TaskHandler::class )
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
		$taskHandler = $this->getMockBuilder( ActionableTask::class )
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
		$webRequest = $this->getMockBuilder( WebRequest::class )
			->disableOriginalConstructor()
			->getMock();

		$webRequest->expects( $this->once() )
			->method( 'getText' )
			->with( 'action' )
			->willReturn( 'foo' );

		$taskHandler = $this->getMockBuilder( ActionableTask::class )
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
