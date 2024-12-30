<?php

namespace SMW\Tests\SQLStore;

use SMW\MediaWiki\Database;
use SMW\SQLStore\PropertyStatisticsStore;
use SMW\SQLStore\SQLStore;
use SMW\Tests\SMWIntegrationTestCase;
use SMW\Tests\PHPUnitCompat;
use Wikimedia\Rdbms\DBQueryError;

/**
 * @covers \SMW\SQLStore\PropertyStatisticsStore
 * @group semantic-mediawiki
 *
 * @group medium
 * @group Database
 *
 * @license GNU GPL v2+
 * @since 1.9
 *
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class PropertyStatisticsStoreTest extends SMWIntegrationTestCase {

	use PHPUnitCompat;

	protected $statsTable = null;

	/**
	 * On the Windows platform pow( 2 , 31 ) returns with
	 * "MWException: The value to add must be a positive integer" therefore
	 * return true if this test runs on Windows
	 *
	 * @return boolean
	 */
	private function isWinOS() {
		return strtoupper( substr( PHP_OS, 0, 3 ) ) === 'WIN';
	}

	public function testDeleteAll() {
		$statsTable = new PropertyStatisticsStore(
			$this->getStore()->getConnection( 'mw.db' )
		);

		$this->assertTrue( $statsTable->deleteAll() !== false );
		$this->assertTrue( $statsTable->deleteAll() !== false );

		$statsTable->insertUsageCount( 1, 1 );

		$this->assertTrue( $statsTable->deleteAll() !== false );

		$this->assertTrue( $statsTable->getUsageCounts( [ 1, 2 ] ) === [] );
	}

	public function usageCountProvider() {
		$usageCounts = [];

		$usageCounts[] = [ 1, 0 ];
		$usageCounts[] = [ 2, 1 ];

		for ( $propId = 3; $propId <= 42; $propId++ ) {
			$usageCounts[] = [ $propId, mt_rand( 0, 100000 ) ];
		}

		$usageCounts[] = [ 9001, $this->isWinOS() ? pow( 2, 30 ) : pow( 2, 31 ) ];

		return $usageCounts;
	}

	/**
	 * @return \SMW\Store\PropertyStatisticsStore
	 */
	protected function getTable() {
		if ( $this->statsTable === null ) {

			$this->statsTable = new PropertyStatisticsStore(
				$this->getStore()->getConnection( 'mw.db' )
			);

			$this->assertTrue( $this->statsTable->deleteAll() !== false );
		}

		return $this->statsTable;
	}

	/**
	 * @dataProvider usageCountProvider
	 */
	public function testGetUsageCount( $propId, $usageCount ) {
		$table = $this->getTable();

		$table->insertUsageCount( $propId, $usageCount );

		$this->assertEquals(
			$usageCount,
			$table->getUsageCount( $propId )
		);
	}

	public function testAddToUsageCountWithInvalidCountThrowsException() {
		$connection = $this->createMock( Database::class );

		$instance = new PropertyStatisticsStore(
			$connection,
			'foo'
		);

		$this->expectException( '\SMW\SQLStore\Exception\PropertyStatisticsInvalidArgumentException' );
		$instance->addToUsageCount( 12, 'foo' );
	}

	public function testAddToUsageCountWithInvalidIdThrowsException() {
		$connection = $this->createMock( Database::class );

		$instance = new PropertyStatisticsStore(
			$connection
		);

		$this->expectException( '\SMW\SQLStore\Exception\PropertyStatisticsInvalidArgumentException' );
		$instance->addToUsageCount( 'Foo', 12 );
	}

	/**
	 * @dataProvider usageCountProvider
	 *
	 * @param int $propId
	 * @param int $usageCount
	 */
	public function testInsertUsageCount( $propId, $usageCount ) {
		$table = $this->getTable();

		$this->assertTrue( $table->insertUsageCount( $propId, $usageCount ) );

		$usageCounts = $table->getUsageCounts( [ $propId ] );

		$this->assertArrayHasKey( $propId, $usageCounts );
		$this->assertEquals( $usageCount, $usageCounts[$propId] );

		$change = mt_rand( max( -100, -$usageCount ), 100 );

		$this->assertTrue( $table->addToUsageCount( $propId, $change ) !== false );

		$usageCounts = $table->getUsageCounts( [ $propId ] );

		$this->assertArrayHasKey( $propId, $usageCounts );
		$this->assertEquals( $usageCount + $change, $usageCounts[$propId], 'Testing addToUsageCount with ' . $change );
	}

	public function testAddToUsageCounts() {
		$statsTable = new PropertyStatisticsStore(
			$this->getStore()->getConnection( 'mw.db' )
		);

		$this->assertTrue( $statsTable->deleteAll() !== false );

		$counts = [
			1 => 42,
			2 => 0,
			9001 => 9001,
			9002 => $this->isWinOS() ? pow( 2, 30 ) : pow( 2, 31 ),
			9003 => 1,
		];

		foreach ( $counts as $propId => $count ) {
			$this->assertTrue( $statsTable->insertUsageCount( $propId, $count ) !== false );
		}

		$additions = [
			2 => 42,
			9001 => -9000,
			9003 => 0,
		];

		$this->assertTrue(
			$statsTable->addToUsageCounts( $additions ) !== false
		);

		foreach ( $additions as $propId => $addition ) {
			$counts[$propId] += $addition;
		}

		$this->assertEquals(
			$counts,
			$statsTable->getUsageCounts( array_keys( $counts ) )
		);
	}

	public function testAddToUsageCountsOnTransactionIdle() {
		$connection = $this->createMock( Database::class );

		$connection->expects( $this->once() )
			->method( 'onTransactionCommitOrIdle' )
			->willReturnCallback( function ( $callback ) {
				return call_user_func( $callback );
			}
			);

		$connection->expects( $this->atLeastOnce() )
			->method( 'update' );

		$instance = new PropertyStatisticsStore(
			$connection
		);

		$additions = [
			2 => 42,
			9001 => -9000,
			9003 => 0,
		];

		$instance->waitOnTransactionIdle();

		$this->assertTrue(
			$instance->addToUsageCounts( $additions )
		);
	}

	public function testAddToUsageCountsWillNotWaitOnTransactionIdleWhenCommandLineModeIsActive() {
		$connection = $this->createMock( Database::class );

		$connection->expects( $this->never() )
			->method( 'onTransactionCommitOrIdle' );

		$connection->expects( $this->atLeastOnce() )
			->method( 'update' );

		$instance = new PropertyStatisticsStore(
			$connection
		);

		$additions = [
			2 => 42,
			9001 => -9000,
			9003 => 0,
		];

		$instance->isCommandLineMode( true );
		$instance->waitOnTransactionIdle();

		$instance->addToUsageCounts( $additions );
	}

	public function testInsertUsageCountWithArrayValue() {
		$tableName = 'Foo';

		$connection = $this->createMock( Database::class );

		$connection->expects( $this->once() )
			->method( 'insert' )
			->with(
				$this->stringContains( SQLStore::PROPERTY_STATISTICS_TABLE ),
				$this->equalTo(
					[
						'usage_count' => 1,
						'null_count'  => 9999,
						'p_id' => 42
					] ),
				$this->anything() );

		$instance = new PropertyStatisticsStore(
			$connection
		);

		$instance->insertUsageCount( 42, [ 1, 9999 ] );
	}

	public function testAddToUsageCountsWithArrayValue() {
		$connection = $this->createMock( Database::class );

		$connection->expects( $this->any() )
			->method( 'addQuotes' )
			->willReturnArgument( 0 );

		$connection->expects( $this->once() )
			->method( 'update' )
			->with(
				$this->stringContains( SQLStore::PROPERTY_STATISTICS_TABLE ),
				$this->equalTo(
					[
						'usage_count = usage_count + 1',
						'null_count = null_count + 9999'
					] ),
				[
						'p_id' => 42
					],
				$this->anything() );

		$instance = new PropertyStatisticsStore(
			$connection
		);

		$instance->addToUsageCounts( [ 42 => [ 'usage' => 1, 'null' => 9999 ] ] );
	}

	public function testSetUsageCountWithArrayValue() {
		$connection = $this->createMock( Database::class );

		$connection->expects( $this->once() )
			->method( 'update' )
			->with(
				$this->stringContains( SQLStore::PROPERTY_STATISTICS_TABLE ),
				$this->equalTo(
					[
						'usage_count' => 1,
						'null_count' => 9999
					] ),
				[
						'p_id' => 42
					],
				$this->anything() );

		$instance = new PropertyStatisticsStore(
			$connection
		);

		$instance->setUsageCount( 42, [ 1, 9999 ] );
	}

	public function testUpsertOnInsertUsageCount() {
		$error = $this->createMock( DBQueryError::class );

		$connection = $this->createMock( Database::class );

		$connection->expects( $this->once() )
			->method( 'insert' )
			->willThrowException( $error );

		$connection->expects( $this->once() )
			->method( 'update' )
			->with(
				$this->stringContains( SQLStore::PROPERTY_STATISTICS_TABLE ),
				$this->equalTo(
					[
						'usage_count' => 12,
						'null_count' => 0
					] ),
				[ 'p_id' => 42 ],
				$this->anything() );

		$instance = new PropertyStatisticsStore(
			$connection
		);

		$instance->insertUsageCount( 42, 12 );
	}
}
