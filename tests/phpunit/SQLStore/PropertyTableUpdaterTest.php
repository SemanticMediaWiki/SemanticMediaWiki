<?php

namespace SMW\Tests\SQLStore;

use SMW\SQLStore\PropertyTableUpdater;
use SMW\Parameters;
use SMW\Tests\PHPUnitCompat;

/**
 * @covers \SMW\SQLStore\PropertyTableUpdater
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class PropertyTableUpdaterTest extends \PHPUnit\Framework\TestCase {

	use PHPUnitCompat;

	private $store;
	private $idTable;
	private $connection;
	private $propertyTable;
	private $propertyStatisticsStore;
	private $propertyChangeListener;

	protected function setUp(): void {
		parent::setUp();

		$this->connection = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$this->idTable = $this->getMockBuilder( '\SMW\SQLStore\EntityStore\EntityIdManager' )
			->disableOriginalConstructor()
			->getMock();

		$this->store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->getMock();

		$this->store->expects( $this->any() )
			->method( 'getObjectIds' )
			->willReturn( $this->idTable );

		$this->store->expects( $this->any() )
			->method( 'getConnection' )
			->willReturn( $this->connection );

		$this->propertyTable = $this->getMockBuilder( '\SMW\SQLStore\PropertyTableDefinition' )
			->disableOriginalConstructor()
			->getMock();

		$this->propertyStatisticsStore = $this->getMockBuilder( '\SMW\SQLStore\PropertyStatisticsStore' )
			->disableOriginalConstructor()
			->getMock();

		$this->propertyChangeListener = $this->getMockBuilder( '\SMW\Listener\ChangeListener\ChangeListeners\PropertyChangeListener' )
			->disableOriginalConstructor()
			->getMock();
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			PropertyTableUpdater::class,
			new PropertyTableUpdater( $this->store, $this->propertyStatisticsStore )
		);
	}

	public function testUpdate_OnEmptyInsertRows() {
		$this->store->expects( $this->any() )
			->method( 'getPropertyTables' )
			->willReturn( [] );

		$this->propertyStatisticsStore->expects( $this->once() )
			->method( 'addToUsageCounts' );

		$instance = new PropertyTableUpdater(
			$this->store,
			$this->propertyStatisticsStore
		);

		$params = new Parameters(
			[
				'insert_rows' => [],
				'delete_rows' => [],
				'new_hashes'  => []
			]
		);

		$instance->update( 42, $params );
	}

	public function testUpdate_WithInsertRows() {
		$this->connection->expects( $this->once() )
			->method( 'insert' );

		$this->connection->expects( $this->once() )
			->method( 'delete' );

		$this->idTable->expects( $this->once() )
			->method( 'setPropertyTableHashes' );

		$this->propertyTable->expects( $this->any() )
			->method( 'usesIdSubject' )
			->willReturn( true );

		$this->store->expects( $this->any() )
			->method( 'getPropertyTables' )
			->willReturn( [ 'table_foo' => $this->propertyTable ] );

		$this->propertyStatisticsStore->expects( $this->once() )
			->method( 'addToUsageCounts' )
			->with( $this->equalTo( [ 99998 => -1, 99999 => 1 ] ) );

		$instance = new PropertyTableUpdater(
			$this->store,
			$this->propertyStatisticsStore
		);

		$instance->setPropertyChangeListener(
			$this->propertyChangeListener
		);

		$params = new Parameters(
			[
				'insert_rows' => [
					'table_foo' => [
						[ 's_id' => 1001, 'p_id' => 99999 ]
					]
				],
				'delete_rows' => [
					'table_foo' => [
						[ 's_id' => 1001, 'p_id' => 99998 ]
					]
				],
				'new_hashes'  => []
			]
		);

		$instance->update( 42, $params );
	}

	public function testUpdate_Touched() {
		$this->connection->expects( $this->once() )
			->method( 'timestamp' )
			->willReturn( '19700101000000' );

		$this->connection->expects( $this->once() )
			->method( 'update' )
			->with(
				$this->anything(),
				[ 'smw_touched' => '19700101000000' ],
				$this->equalTo( [ 'smw_id' => [ 1001, 99999, 99998 ] ] ) );

		$this->propertyTable->expects( $this->any() )
			->method( 'usesIdSubject' )
			->willReturn( true );

		$this->store->expects( $this->any() )
			->method( 'getPropertyTables' )
			->willReturn( [ 'table_foo' => $this->propertyTable ] );

		$instance = new PropertyTableUpdater(
			$this->store,
			$this->propertyStatisticsStore
		);

		$instance->setPropertyChangeListener(
			$this->propertyChangeListener
		);

		$params = new Parameters(
			[
				'insert_rows' => [
					'table_foo' => [
						[ 's_id' => 1001, 'p_id' => 99999 ]
					]
				],
				'delete_rows' => [
					'table_foo' => [
						[ 's_id' => 1001, 'p_id' => 99998 ]
					]
				],
				'new_hashes'  => []
			]
		);

		$instance->update( 42, $params );
	}

	public function testUpdate_WithInsertRowsButMissingIdFieldThrowsException() {
		$this->store->expects( $this->any() )
			->method( 'getPropertyTables' )
			->willReturn( [ 'table_foo' => $this->propertyTable ] );

		$instance = new PropertyTableUpdater(
			$this->store,
			$this->propertyStatisticsStore
		);

		$params = new Parameters(
			[
				'insert_rows' => [
					'table_foo' => []
				],
				'delete_rows' => [
					'table_foo' => []
				],
				'new_hashes'  => []
			]
		);

		$this->expectException( '\SMW\SQLStore\Exception\TableMissingIdFieldException' );
		$instance->update( 42, $params );
	}

}
