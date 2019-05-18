<?php

namespace SMW\Tests\Elastic\Admin;

use SMW\Elastic\Admin\ElasticClientTaskHandler;
use SMW\Elastic\Connection\DummyClient;

/**
 * @covers \SMW\Elastic\Admin\ElasticClientTaskHandler
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class ElasticClientTaskHandlerTest extends \PHPUnit_Framework_TestCase {

	private $outputFormatter;
	private $webRequest;
	private $store;

	protected function setUp() {

		$this->outputFormatter = $this->getMockBuilder( '\SMW\MediaWiki\Specials\Admin\OutputFormatter' )
			->disableOriginalConstructor()
			->getMock();

		$this->webRequest = $this->getMockBuilder( '\WebRequest' )
			->disableOriginalConstructor()
			->getMock();

		$this->store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->setMethods( [ 'getConnection' ] )
			->getMockForAbstractClass();

		$this->store->expects( $this->any() )
			->method( 'getConnection' )
			->will( $this->returnValue( new DummyClient() ) );
	}

	public function testCanConstruct() {

		$this->assertInstanceOf(
			ElasticClientTaskHandler::class,
			new ElasticClientTaskHandler( $this->outputFormatter )
		);
	}

	public function testIsTask() {

		$task = $this->getMockBuilder( '\SMW\MediaWiki\Specials\Admin\TaskHandler' )
			->disableOriginalConstructor()
			->setMethods( [ 'getTask' ] )
			->getMockForAbstractClass();

		$task->expects( $this->once() )
			->method( 'getTask' )
			->will( $this->returnValue( 'Foo' ) );

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
			->will( $this->returnValue( true ) );

		$store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->setMethods( [ 'getConnection' ] )
			->getMockForAbstractClass();

		$store->expects( $this->any() )
			->method( 'getConnection' )
			->will( $this->returnValue( $client ) );

		$instance = new ElasticClientTaskHandler(
			$this->outputFormatter
		);

		$instance->setStore( $store );

		$this->assertInternalType(
			'string',
			$instance->getHtml()
		);
	}

	public function testHandleRequest_OnNoAvailableNodes() {

		$this->outputFormatter->expects( $this->once() )
			->method( 'addParentLink' )
			->with(	$this->equalTo( [ 'tab' => 'supplement' ] ) );

		$instance = new ElasticClientTaskHandler(
			$this->outputFormatter
		);

		$instance->setStore( $this->store );
		$instance->handleRequest( $this->webRequest );
	}

}
