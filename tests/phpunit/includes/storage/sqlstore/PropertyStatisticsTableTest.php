<?php

namespace SMW\Test\SQLStore;

use SMW\SQLStore\PropertyStatisticsTable;
use SMW\StoreFactory;

/**
 * @covers \SMW\SQLStore\PropertyStatisticsTable
 *
 * @file
 * @since 1.9
 *
 * @ingroup SMW
 * @ingroup Test
 *
 * @group SMW
 * @group SMWExtension
 * @group mediawiki-database
 * @group Database
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class PropertyStatisticsTableTest extends \MediaWikiTestCase {

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

		$store = StoreFactory::getStore();

		if ( !( $store instanceof \SMWSQLStore3 ) ) {
			$this->markTestSkipped( 'Test only applicable to SMWSQLStore3' );
		}

		$statsTable = new PropertyStatisticsTable( $store , \SMWSQLStore3::PROPERTY_STATISTICS_TABLE );

		$this->assertTrue( $statsTable->deleteAll() !== false );
		$this->assertTrue( $statsTable->deleteAll() !== false );

		$statsTable->insertUsageCount( 1, 1 );

		$this->assertTrue( $statsTable->deleteAll() !== false );

		$this->assertTrue( $statsTable->getUsageCounts( array( 1, 2 ) ) === array() );
	}

	public function usageCountProvider() {
		$usageCounts = array();

		$usageCounts[] = array( 1, 0 );
		$usageCounts[] = array( 2, 1 );

		for ( $propId = 3; $propId <= 42; $propId++ ) {
			$usageCounts[] = array( $propId, mt_rand( 0, 100000 ) );
		}

		$usageCounts[] = array( 9001, $this->isWinOS() ? pow( 2 , 30 ) : pow( 2 , 31 ) );

		return $usageCounts;
	}

	protected $statsTable = null;

	/**
	 * @return PropertyStatisticsStore
	 */
	protected function getTable( $store ) {
		if ( $this->statsTable === null ) {
			$this->statsTable = new PropertyStatisticsTable( $store, \SMWSQLStore3::PROPERTY_STATISTICS_TABLE );
			$this->assertTrue( $this->statsTable->deleteAll() !== false );
		}

		return $this->statsTable;
	}

	/**
	 * @dataProvider usageCountProvider
	 *
	 * @param int $propId
	 * @param int $usageCount
	 */
	public function testInsertUsageCount( $propId, $usageCount ) {

		$store = StoreFactory::getStore();

		if ( !( $store instanceof \SMWSQLStore3 ) ) {
			$this->markTestSkipped( 'Test only applicable to SMWSQLStore3' );
		}

		$table = $this->getTable( $store );

		$this->assertTrue( $table->insertUsageCount( $propId, $usageCount ) );

		$usageCounts = $table->getUsageCounts( array( $propId ) );

		$this->assertArrayHasKey( $propId, $usageCounts );
		$this->assertEquals( $usageCount, $usageCounts[$propId] );

		$change = mt_rand( max( -100, -$usageCount ), 100 );

		$this->assertTrue( $table->addToUsageCount( $propId, $change ) !== false );

		$usageCounts = $table->getUsageCounts( array( $propId ) );

		$this->assertArrayHasKey( $propId, $usageCounts );
		$this->assertEquals( $usageCount + $change, $usageCounts[$propId], 'Testing addToUsageCount with ' . $change );
	}

	public function testAddToUsageCounts() {

		$store = StoreFactory::getStore();

		if ( !( $store instanceof \SMWSQLStore3 ) ) {
			$this->markTestSkipped( 'Test only applicable to SMWSQLStore3' );
		}

		$statsTable = new \SMW\SQLStore\PropertyStatisticsTable( $store, \SMWSQLStore3::PROPERTY_STATISTICS_TABLE );
		$this->assertTrue( $statsTable->deleteAll() !== false );

		$counts = array(
			1 => 42,
			2 => 0,
			9001 => 9001,
			9002 => $this->isWinOS() ? pow( 2 , 30 ) : pow( 2 , 31 ),
			9003 => 1,
		);

		foreach ( $counts as $propId => $count ) {
			$this->assertTrue( $statsTable->insertUsageCount( $propId, $count ) !== false );
		}

		$additions = array(
			2 => 42,
			9001 => -9000,
			9003 => 0,
		);

		$this->assertTrue( $statsTable->addToUsageCounts( $additions ) !== false );

		foreach ( $additions as $propId => $addition ) {
			$counts[$propId] += $addition;
		}

		$this->assertEquals( $counts, $statsTable->getUsageCounts( array_keys( $counts ) ) );
	}

}
