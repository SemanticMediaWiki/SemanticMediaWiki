<?php

namespace SMW\Tests\Elastic\Admin;

use SMW\Elastic\Admin\ReplicationInfoProvider;
use SMW\Elastic\Connection\DummyClient;

/**
 * @covers \SMW\Elastic\Admin\ReplicationInfoProvider
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class ReplicationInfoProviderTest extends \PHPUnit_Framework_TestCase {

	private $outputFormatter;
	private $webRequest;
	private $checkReplicationTask;
	private $entityCache;
	private $store;

	protected function setUp() {

		$this->outputFormatter = $this->getMockBuilder( '\SMW\MediaWiki\Specials\Admin\OutputFormatter' )
			->disableOriginalConstructor()
			->getMock();

		$this->checkReplicationTask = $this->getMockBuilder( '\SMW\Elastic\Indexer\Replication\CheckReplicationTask' )
			->disableOriginalConstructor()
			->getMock();

		$this->entityCache = $this->getMockBuilder( '\SMW\EntityCache' )
			->disableOriginalConstructor()
			->setMethods( [ 'fetch' ] )
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
			ReplicationInfoProvider::class,
			new ReplicationInfoProvider( $this->outputFormatter, $this->checkReplicationTask, $this->entityCache )
		);
	}

	public function testGetTask() {

		$instance = new ReplicationInfoProvider(
			$this->outputFormatter,
			$this->checkReplicationTask,
			$this->entityCache
		);

		$this->assertEquals(
			'replication',
			$instance->getSupplementTask()
		);

		$this->assertEquals(
			'elastic/replication',
			$instance->getTask()
		);
	}

	public function testGetHtml() {

		$instance = new ReplicationInfoProvider(
			$this->outputFormatter,
			$this->checkReplicationTask,
			$this->entityCache
		);

		$this->assertInternalType(
			'string',
			$instance->getHtml()
		);
	}

	public function tesHandleRequest_NoFailures() {

		$this->outputFormatter->expects( $this->once() )
			->method( 'addParentLink' )
			->with(	$this->equalTo( [ 'action' => 'elastic' ] ) );

		$this->checkReplicationTask->expects( $this->once() )
			->method( 'getReplicationFailures' )
			->will( $this->returnValue( [] ) );

		$instance = new ReplicationInfoProvider(
			$this->outputFormatter,
			$this->checkReplicationTask,
			$this->entityCache
		);

		$instance->setStore( $this->store );
		$instance->handleRequest( $this->webRequest );
	}

	public function testHandleRequest_WithFailuresOnPages() {

		$ns = NS_FILE;

		$this->checkReplicationTask->expects( $this->once() )
			->method( 'getReplicationFailures' )
			->will( $this->returnValue( [ 'Foo#0##', "Bar#$ns##" ] ) );

		$instance = new ReplicationInfoProvider(
			$this->outputFormatter,
			$this->checkReplicationTask,
			$this->entityCache
		);

		$instance->setStore( $this->store );
		$instance->handleRequest( $this->webRequest );
	}


}
