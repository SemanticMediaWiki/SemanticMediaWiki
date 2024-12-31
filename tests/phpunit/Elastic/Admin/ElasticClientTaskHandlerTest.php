<?php

namespace SMW\Tests\Elastic\Admin;

use SMW\Elastic\Admin\ElasticClientTaskHandler;
use SMW\Elastic\Connection\DummyClient;
use SMW\Tests\PHPUnitCompat;

/**
 * @covers \SMW\Elastic\Admin\ElasticClientTaskHandler
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class ElasticClientTaskHandlerTest extends \PHPUnit\Framework\TestCase {

	use PHPUnitCompat;

	private $outputFormatter;
	private $webRequest;
	private $store;

	protected function setUp(): void {
		$this->outputFormatter = $this->getMockBuilder( '\SMW\MediaWiki\Specials\Admin\OutputFormatter' )
			->disableOriginalConstructor()
			->getMock();

		$this->webRequest = $this->getMockBuilder( '\WebRequest' )
			->disableOriginalConstructor()
			->getMock();

		$this->store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->onlyMethods( [ 'getConnection' ] )
			->getMockForAbstractClass();

		$this->store->expects( $this->any() )
			->method( 'getConnection' )
			->willReturn( new DummyClient() );
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			ElasticClientTaskHandler::class,
			new ElasticClientTaskHandler( $this->outputFormatter )
		);
	}

	public function testIsTask() {
		$task = $this->getMockBuilder( '\SMW\MediaWiki\Specials\Admin\ActionableTask' )
			->disableOriginalConstructor()
			->getMock();

		$task->expects( $this->once() )
			->method( 'getTask' )
			->willReturn( 'Foo' );

		$instance = new ElasticClientTaskHandler(
			$this->outputFormatter,
			[
				$task
			]
		);

		$this->assertTrue(
			$instance->isTaskFor( 'Foo' )
		);
	}

	public function testGetHtml_OnAvailableNodes() {
		$this->outputFormatter->expects( $this->once() )
			->method( 'createSpecialPageLink' );

		$client = $this->getMockBuilder( '\SMW\Elastic\Connection\DummyClient' )
			->disableOriginalConstructor()
			->getMock();

		$client->expects( $this->any() )
			->method( 'ping' )
			->willReturn( true );

		$store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->onlyMethods( [ 'getConnection' ] )
			->getMockForAbstractClass();

		$store->expects( $this->any() )
			->method( 'getConnection' )
			->willReturn( $client );

		$instance = new ElasticClientTaskHandler(
			$this->outputFormatter
		);

		$instance->setStore( $store );

		$this->assertIsString(

			$instance->getHtml()
		);
	}

	public function testHandleRequest_OnNoAvailableNodes() {
		$this->outputFormatter->expects( $this->once() )
			->method( 'addParentLink' )
			->with(	[ 'tab' => 'supplement' ] );

		$instance = new ElasticClientTaskHandler(
			$this->outputFormatter
		);

		$instance->setStore( $this->store );
		$instance->handleRequest( $this->webRequest );
	}

}
