<?php

namespace SMW\Tests\Elastic\Admin;

use MediaWiki\Request\WebRequest;
use PHPUnit\Framework\TestCase;
use SMW\Elastic\Admin\ElasticClientTaskHandler;
use SMW\Elastic\Connection\DummyClient;
use SMW\MediaWiki\Specials\Admin\ActionableTask;
use SMW\MediaWiki\Specials\Admin\OutputFormatter;
use SMW\Store;

/**
 * @covers \SMW\Elastic\Admin\ElasticClientTaskHandler
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 3.0
 *
 * @author mwjames
 */
class ElasticClientTaskHandlerTest extends TestCase {

	private $outputFormatter;
	private $webRequest;
	private $store;

	protected function setUp(): void {
		$this->outputFormatter = $this->getMockBuilder( OutputFormatter::class )
			->disableOriginalConstructor()
			->getMock();

		$this->webRequest = $this->getMockBuilder( WebRequest::class )
			->disableOriginalConstructor()
			->getMock();

		$this->store = $this->getMockBuilder( Store::class )
			->disableOriginalConstructor()
			->setMethods( [ 'getConnection' ] )
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
		$task = $this->getMockBuilder( ActionableTask::class )
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

		$client = $this->getMockBuilder( DummyClient::class )
			->disableOriginalConstructor()
			->getMock();

		$client->expects( $this->any() )
			->method( 'ping' )
			->willReturn( true );

		$store = $this->getMockBuilder( Store::class )
			->disableOriginalConstructor()
			->setMethods( [ 'getConnection' ] )
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
