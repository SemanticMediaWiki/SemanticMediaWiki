<?php

namespace SMW\Tests\MediaWiki\Specials\Admin;

use MediaWiki\Request\WebRequest;
use PHPUnit\Framework\TestCase;
use SMW\MediaWiki\Specials\Admin\ActionableTask;
use SMW\MediaWiki\Specials\Admin\OutputFormatter;
use SMW\MediaWiki\Specials\Admin\SupplementTaskHandler;
use SMW\MediaWiki\Specials\Admin\TaskHandler;
use SMW\Store;
use SMW\Tests\TestEnvironment;

/**
 * @covers \SMW\MediaWiki\Specials\Admin\SupplementTaskHandler
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 3.1
 *
 * @author mwjames
 */
class SupplementTaskHandlerTest extends TestCase {

	private $testEnvironment;
	private $store;
	private $outputFormatter;

	protected function setUp(): void {
		parent::setUp();

		$this->testEnvironment = new TestEnvironment();

		$this->store = $this->getMockBuilder( Store::class )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$this->outputFormatter = $this->getMockBuilder( OutputFormatter::class )
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
			SupplementTaskHandler::class,
			new SupplementTaskHandler( $this->outputFormatter, [] )
		);
	}

	public function testGetHtml() {
		$taskHandler = $this->getMockBuilder( TaskHandler::class )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$instance = new SupplementTaskHandler(
			$this->outputFormatter,
			[
				$taskHandler
			]
		);

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

		$instance = new SupplementTaskHandler(
			$this->outputFormatter,
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

		$instance = new SupplementTaskHandler(
			$this->outputFormatter,
			[
				$taskHandler
			]
		);

		$instance->setStore( $this->store );
		$instance->handleRequest( $webRequest );
	}

}
