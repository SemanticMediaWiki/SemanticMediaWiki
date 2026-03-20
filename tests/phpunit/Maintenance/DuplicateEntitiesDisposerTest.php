<?php

namespace SMW\Tests\Maintenance;

use Onoi\Cache\Cache;
use Onoi\MessageReporter\MessageReporter;
use PHPUnit\Framework\TestCase;
use SMW\Maintenance\DuplicateEntitiesDisposer;
use SMW\MediaWiki\Connection\Database;
use SMW\SQLStore\PropertyTableIdReferenceDisposer;
use SMW\SQLStore\SQLStore;
use stdClass;

/**
 * @covers \SMW\Maintenance\DuplicateEntitiesDisposer
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 3.0
 *
 * @author mwjames
 */
class DuplicateEntitiesDisposerTest extends TestCase {

	private $store;
	private $cache;
	private $messageReporter;
	private $connection;

	protected function setUp(): void {
		$propertyTableIdReferenceDisposer = $this->getMockBuilder( PropertyTableIdReferenceDisposer::class )
			->disableOriginalConstructor()
			->getMock();

		$this->store = $this->getMockBuilder( SQLStore::class )
			->disableOriginalConstructor()
			->getMock();

		$this->store->expects( $this->any() )
			->method( 'service' )
			->with( 'PropertyTableIdReferenceDisposer' )
			->willReturn( $propertyTableIdReferenceDisposer );

		$this->messageReporter = $this->getMockBuilder( MessageReporter::class )
			->disableOriginalConstructor()
			->getMock();

		$this->connection = $this->getMockBuilder( Database::class )
			->disableOriginalConstructor()
			->getMock();

		$this->cache = $this->getMockBuilder( Cache::class )
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
			->willReturn( $idTable );

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

		$row = new stdClass;
		$row->smw_id = 42;

		$this->connection->expects( $this->atLeastOnce() )
			->method( 'select' )
			->with(
				$this->anything(),
				$this->anything(),
				$record,
				$this->anything() )
			->willReturn( [ $row ] );

		$this->store->expects( $this->atLeastOnce() )
			->method( 'getConnection' )
			->willReturn( $this->connection );

		$this->store->expects( $this->any() )
			->method( 'getPropertyTables' )
			->willReturn( [] );

		$instance = new DuplicateEntitiesDisposer(
			$this->store
		);

		$instance->setMessageReporter(
			$this->messageReporter
		);

		$duplicates = [
			SQLStore::ID_TABLE => [ $record ]
		];

		$instance->verifyAndDispose( $duplicates );
	}

}
