<?php

namespace SMW\Tests\MediaWiki\Specials\Admin;

use SMW\MediaWiki\Specials\Admin\MaintenanceTaskHandler;
use SMW\Tests\TestEnvironment;

/**
 * @covers \SMW\MediaWiki\Specials\Admin\MaintenanceTaskHandler
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.1
 *
 * @author mwjames
 */
class MaintenanceTaskHandlerTest extends \PHPUnit_Framework_TestCase {

	private $testEnvironment;
	private $store;
	private $outputFormatter;
	private $fileFetcher;

	protected function setUp() {
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

	protected function tearDown() {
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
			->will( $this->returnValue( [] ) );

		$instance = new MaintenanceTaskHandler(
			$this->outputFormatter,
			$this->fileFetcher,
			[
				$taskHandler
			]
		);

		$this->assertInternalType(
			'string',
			$instance->getHtml()
		);
	}

	public function testIsTaskFor() {

		$taskHandler = $this->getMockBuilder( '\SMW\MediaWiki\Specials\Admin\TaskHandler' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$taskHandler->expects( $this->once() )
			->method( 'isTaskFor' )
			->with( $this->equalTo( 'foo' ) )
			->will( $this->returnValue( true ) );

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

		$taskHandler = $this->getMockBuilder( '\SMW\MediaWiki\Specials\Admin\TaskHandler' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$taskHandler->expects( $this->once() )
			->method( 'isTaskFor' )
			->will( $this->returnValue( true ) );

		$taskHandler->expects( $this->once() )
			->method( 'handleRequest' )
			->with( $this->equalTo( $webRequest ) );

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
