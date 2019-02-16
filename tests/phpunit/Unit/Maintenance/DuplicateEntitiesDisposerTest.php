<?php

namespace SMW\Tests\Maintenance;

use FakeResultWrapper;
use SMW\Maintenance\DuplicateEntitiesDisposer;

/**
 * @covers \SMW\Maintenance\DuplicateEntitiesDisposer
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class DuplicateEntitiesDisposerTest extends \PHPUnit_Framework_TestCase {

	private $store;
	private $cache;
	private $messageReporter;
	private $connection;
	private $propertyTableIdReferenceFinder;

	protected function setUp() {

		$this->store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->getMock();

		$this->propertyTableIdReferenceFinder = $this->getMockBuilder( '\SMW\SQLStore\PropertyTableIdReferenceFinder' )
			->disableOriginalConstructor()
			->getMock();

		$this->store->expects( $this->any() )
			->method( 'getPropertyTableIdReferenceFinder' )
			->will( $this->returnValue( $this->propertyTableIdReferenceFinder ) );

		$this->messageReporter = $this->getMockBuilder( '\Onoi\MessageReporter\MessageReporter' )
			->disableOriginalConstructor()
			->getMock();

		$this->connection = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$this->cache = $this->getMockBuilder( '\Onoi\Cache\Cache' )
			->disableOriginalConstructor()
			->getMock();
	}

	public function testCanConstruct() {

		$this->assertInstanceOf(
			DuplicateEntitiesDisposer::class,
			new DuplicateEntitiesDisposer( $this->store )
		);
	}

	public function testFindDuplicateEntityRecords() {

		$idTable = $this->getMockBuilder( '\stdClss' )
			->disableOriginalConstructor()
			->setMethods( [ 'findDuplicates' ] )
			->getMock();

		$this->store->expects( $this->atLeastOnce() )
			->method( 'getObjectIds' )
			->will( $this->returnValue( $idTable ) );

		$instance = new DuplicateEntitiesDisposer(
			$this->store
		);

		$instance->findDuplicates();
	}

	public function testVerifyAndDispose_NoDuplicates() {

		$this->store->expects( $this->never() )
			->method( 'getConnection' );

		$instance = new DuplicateEntitiesDisposer(
			$this->store
		);

		$instance->setMessageReporter(
			$this->messageReporter
		);

		$instance->verifyAndDispose( [] );
	}

	public function testVerifyAndDispose_NonIterable() {

		$this->store->expects( $this->never() )
			->method( 'getConnection' );

		$instance = new DuplicateEntitiesDisposer(
			$this->store
		);

		$instance->setMessageReporter(
			$this->messageReporter
		);

		$instance->verifyAndDispose( 'Foo' );
	}

	public function testVerifyAndDispose_NoDuplicates_WithCache() {

		$this->store->expects( $this->never() )
			->method( 'getConnection' );

		$this->cache->expects( $this->atLeastOnce() )
			->method( 'delete' );

		$instance = new DuplicateEntitiesDisposer(
			$this->store,
			$this->cache
		);

		$instance->setMessageReporter(
			$this->messageReporter
		);

		$instance->verifyAndDispose( [] );
	}

	public function testVerifyAndDispose_WithDuplicateRecord() {

		$record = [
			'smw_title' => 'Foo',
			'smw_namespace' => 0,
			'smw_iw' => '',
			'smw_subobject' => ''
		];

		$row = new \stdClass;
		$row->smw_id = 42;

		$this->connection->expects( $this->atLeastOnce() )
			->method( 'select' )
			->with(
				$this->anything(),
				$this->anything(),
				$this->equalTo( $record ),
				$this->anything() )
			->will( $this->returnValue( [ $row ] ) );

		$this->store->expects( $this->atLeastOnce() )
			->method( 'getConnection' )
			->will( $this->returnValue( $this->connection ) );

		$idTable = $this->getMockBuilder( '\stdClss' )
			->disableOriginalConstructor()
			->setMethods( [ 'getDataItemById' ] )
			->getMock();

		$this->store->expects( $this->atLeastOnce() )
			->method( 'getObjectIds' )
			->will( $this->returnValue( $idTable ) );

		$this->store->expects( $this->any() )
			->method( 'getPropertyTables' )
			->will( $this->returnValue( [] ) );

		$this->propertyTableIdReferenceFinder->expects( $this->atLeastOnce() )
			->method( 'hasResidualReferenceForId' )
			->will( $this->returnValue( false ) );

		$instance = new DuplicateEntitiesDisposer(
			$this->store
		);

		$instance->setMessageReporter(
			$this->messageReporter
		);

		$duplicates = [
			\SMW\SQLStore\SQLStore::ID_TABLE => [ $record ]
		];

		$instance->verifyAndDispose( $duplicates );
	}

}
