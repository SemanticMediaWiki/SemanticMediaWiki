<?php

namespace SMW\Tests\MediaWiki\Specials\Admin;

use SMW\MediaWiki\Specials\Admin\SupplementTaskHandler;
use SMW\Tests\TestEnvironment;
use SMW\Tests\PHPUnitCompat;

/**
 * @covers \SMW\MediaWiki\Specials\Admin\SupplementTaskHandler
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.1
 *
 * @author mwjames
 */
class SupplementTaskHandlerTest extends \PHPUnit_Framework_TestCase {

	use PHPUnitCompat;

	private $testEnvironment;
	private $store;
	private $outputFormatter;

	protected function setUp() : void {
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

	protected function tearDown() : void {
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

		$taskHandler = $this->getMockBuilder( '\SMW\MediaWiki\Specials\Admin\TaskHandler' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$instance = new SupplementTaskHandler(
			$this->outputFormatter,
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

		$taskHandler = $this->getMockBuilder( '\SMW\MediaWiki\Specials\Admin\ActionableTask' )
			->disableOriginalConstructor()
			->getMock();

		$taskHandler->expects( $this->once() )
			->method( 'isTaskFor' )
			->with( $this->equalTo( 'foo' ) )
			->will( $this->returnValue( true ) );

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

		$webRequest = $this->getMockBuilder( '\WebRequest' )
			->disableOriginalConstructor()
			->getMock();

		$webRequest->expects( $this->once() )
			->method( 'getText' )
			->with( $this->equalTo( 'action' ) )
			->will( $this->returnValue( 'foo' ) );

		$taskHandler = $this->getMockBuilder( '\SMW\MediaWiki\Specials\Admin\ActionableTask' )
			->disableOriginalConstructor()
			->getMock();

		$taskHandler->expects( $this->once() )
			->method( 'isTaskFor' )
			->will( $this->returnValue( true ) );

		$taskHandler->expects( $this->once() )
			->method( 'handleRequest' )
			->with( $this->equalTo( $webRequest ) );

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
