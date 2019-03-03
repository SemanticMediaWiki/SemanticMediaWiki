<?php

namespace SMW\Tests\Elastic\Indexer\Replication;

use SMW\Elastic\Indexer\Replication\CheckReplicationTask;
use SMW\Tests\PHPUnitCompat;
use SMW\DIWikiPage;
use SMW\DIProperty;

/**
 * @covers \SMW\Elastic\Indexer\Replication\CheckReplicationTask
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class CheckReplicationTaskTest extends \PHPUnit_Framework_TestCase {

	use PHPUnitCompat;

	private $store;
	private $replicationStatus;
	private $entityCache;
	private $elasticClient;

	protected function setUp() {

		$idTable = $this->getMockBuilder( '\SMWSql3SmwIds' )
			->disableOriginalConstructor()
			->getMock();

		$this->store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->setMethods( [ 'getObjectIds', 'getConnection' ] )
			->getMockForAbstractClass();

		$this->store->expects( $this->any() )
			->method( 'getObjectIds' )
			->will( $this->returnValue( $idTable ) );

		$this->replicationStatus = $this->getMockBuilder( '\SMW\Elastic\Indexer\Replication\ReplicationStatus' )
			->disableOriginalConstructor()
			->getMock();

		$this->elasticClient = $this->getMockBuilder( '\SMW\Elastic\Connection\Client' )
			->disableOriginalConstructor()
			->getMock();

		$this->entityCache = $this->getMockBuilder( '\SMW\EntityCache' )
			->disableOriginalConstructor()
			->getMock();
	}

	public function testCanConstruct() {

		$this->assertInstanceOf(
			CheckReplicationTask::class,
			new CheckReplicationTask( $this->store, $this->replicationStatus, $this->entityCache )
		);
	}

	public function testCheckReplication_NotExists() {

		$this->replicationStatus->expects( $this->once() )
			->method( 'getModificationDate' )
			->will( $this->returnValue( false ) );

		$this->store->expects( $this->once() )
			->method( 'getPropertyValues' )
			->will( $this->returnValue( [] ) );

		$instance = new CheckReplicationTask(
			$this->store,
			$this->replicationStatus,
			$this->entityCache
		);

		$html = $instance->checkReplication( DIWikiPage::newFromText( 'Foo' ), [] );

		$this->assertContains(
			'smw-highlighter',
			$html
		);
	}

	public function testCheckReplication_File() {

		$config = $this->getMockBuilder( '\SMW\Options' )
			->disableOriginalConstructor()
			->getMock();

		$config->expects( $this->any() )
			->method( 'dotGet' )
			->with(	$this->equalTo( 'indexer.experimental.file.ingest' ) )
			->will( $this->returnValue( true ) );

		$this->elasticClient->expects( $this->any() )
			->method( 'getConfig' )
			->will( $this->returnValue( $config ) );

		$subject = DIWikiPage::newFromText( 'Foo', NS_FILE );

		$this->replicationStatus->expects( $this->once() )
			->method( 'getModificationDate' )
			->will( $this->returnValue( $subject ) );

		$this->store->expects( $this->at( 1 ) )
			->method( 'getPropertyValues' )
			->will( $this->returnValue( [ $subject ] ) );

		$this->store->expects( $this->at( 3 ) )
			->method( 'getPropertyValues' )
			->with(
				$this->equalTo( $subject ),
				$this->equalTo( new DIProperty( '_FILE_ATTCH' ) ) )
			->will( $this->returnValue( [] ) );

		$this->store->expects( $this->any() )
			->method( 'getConnection' )
			->will( $this->returnValue( $this->elasticClient ) );

		$instance = new CheckReplicationTask(
			$this->store,
			$this->replicationStatus,
			$this->entityCache
		);

		$html = $instance->checkReplication( $subject, [] );

		$this->assertContains(
			'smw-highlighter',
			$html
		);
	}

}
