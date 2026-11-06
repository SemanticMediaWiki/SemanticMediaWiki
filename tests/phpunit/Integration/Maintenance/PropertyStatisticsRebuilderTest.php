<?php

namespace SMW\Tests\Integration\Maintenance;

use SMW\DataItems\Blob;
use SMW\DataItems\Property;
use SMW\DataItems\WikiPage;
use SMW\DataModel\SemanticData;
use SMW\Services\ServicesFactory;
use SMW\SQLStore\SQLStore;
use SMW\Tests\SMWIntegrationTestCase;
use Wikimedia\Rdbms\IDatabase;

/**
 * Needs the database, as the rebuilder counts the rows of the property tables.
 *
 * @covers \SMW\Maintenance\PropertyStatisticsRebuilder
 * @group semantic-mediawiki
 * @group Database
 *
 * @license GPL-2.0-or-later
 */
class PropertyStatisticsRebuilderTest extends SMWIntegrationTestCase {

	private const MORE_THAN_ONE_BATCH = 1001;

	public function testCountsTheValuesOfEachProperty(): void {
		[ $twoTexts, $textAndNumber, $unused ] = $this->insertProperties( 'Statistics_prop_', 3 );
		$this->insertTexts( $twoTexts, 2 );
		$this->insertTexts( $textAndNumber, 1 );
		$this->insertNumber( $textAndNumber );

		$this->rebuild();

		$this->assertSame(
			[ [ 2, 0 ], [ 2, 0 ], [ 0, 0 ] ],
			[ $this->fetchCounts( $twoTexts ), $this->fetchCounts( $textAndNumber ), $this->fetchCounts( $unused ) ]
		);
	}

	public function testCountsValuesWithoutContentAsNullsRatherThanUsages(): void {
		[ $property ] = $this->insertProperties( 'Statistics_prop_', 1 );
		$this->insertTexts( $property, 1 );
		$this->insertTextWithoutContent( $property );

		$this->rebuild();

		$this->assertSame( [ 1, 1 ], $this->fetchCounts( $property ) );
	}

	public function testCountsTheValuesOfAFixedPropertyTable(): void {
		$this->storeDisplayTitle( 'StatisticsDisplayTitle' );
		$this->insertRows( 'smw_fpt_dtitle', [ [ 's_id' => 1, 'o_blob' => null, 'o_hash' => null ] ] );
		$this->driftStatistics();

		$this->rebuild();

		$this->assertSame(
			[ 1, 1 ],
			$this->fetchCounts( $this->getStore()->getObjectIds()->getSMWPropertyID( new Property( '_DTITLE' ) ) )
		);
	}

	public function testCountsPropertiesBeyondTheFirstBatch(): void {
		$properties = $this->insertProperties( 'Batch_prop_', self::MORE_THAN_ONE_BATCH );
		$this->insertTextForEach( $properties );

		$this->rebuild();

		$this->assertSame( self::MORE_THAN_ONE_BATCH, $this->countPropertiesUsedOnce( $properties ) );
	}

	private function rebuild(): void {
		$maintenanceFactory = ServicesFactory::getInstance()->newMaintenanceFactory();
		$maintenanceFactory->newPropertyStatisticsRebuilder( $this->getStore() )->rebuild();
	}

	/**
	 * @return int[] The IDs of the new property entities, in order
	 */
	private function insertProperties( string $titlePrefix, int $count ): array {
		$titles = [];

		for ( $i = 1; $i <= $count; $i++ ) {
			$titles[] = $titlePrefix . $i;
		}

		$this->insertRows( SQLStore::ID_TABLE, array_map(
			static fn ( string $title ) => [
				'smw_namespace' => SMW_NS_PROPERTY,
				'smw_title' => $title,
				'smw_iw' => '',
				'smw_subobject' => '',
				'smw_sortkey' => $title,
				'smw_sort' => $title
			],
			$titles
		) );

		return array_map( 'intval', $this->getStore()->getConnection( 'mw.db' )->newSelectQueryBuilder()
			->select( 'smw_id' )
			->from( SQLStore::ID_TABLE )
			->where( [ 'smw_namespace' => SMW_NS_PROPERTY, 'smw_title' => $titles ] )
			->orderBy( 'smw_id' )
			->caller( __METHOD__ )
			->fetchFieldValues() );
	}

	private function insertTexts( int $propertyId, int $count ): void {
		$rows = [];

		for ( $i = 1; $i <= $count; $i++ ) {
			$rows[] = [ 's_id' => $propertyId, 'p_id' => $propertyId, 'o_blob' => null, 'o_hash' => "text $i" ];
		}

		$this->insertRows( 'smw_di_blob', $rows );
	}

	/**
	 * @param int[] $propertyIds
	 */
	private function insertTextForEach( array $propertyIds ): void {
		$this->insertRows( 'smw_di_blob', array_map(
			static fn ( int $id ) => [ 's_id' => $id, 'p_id' => $id, 'o_blob' => null, 'o_hash' => 'text' ],
			$propertyIds
		) );
	}

	private function insertNumber( int $propertyId ): void {
		$this->insertRows( 'smw_di_number', [
			[ 's_id' => $propertyId, 'p_id' => $propertyId, 'o_serialized' => '42', 'o_sortkey' => 42 ]
		] );
	}

	private function insertTextWithoutContent( int $propertyId ): void {
		$this->insertRows( 'smw_di_blob', [
			[ 's_id' => $propertyId, 'p_id' => $propertyId, 'o_blob' => null, 'o_hash' => null ]
		] );
	}

	private function insertRows( string $table, array $rows ): void {
		$this->getStore()->getConnection( 'mw.db' )->newInsertQueryBuilder()
			->insertInto( $table )
			->rows( $rows )
			->caller( __METHOD__ )
			->execute();
	}

	private function storeDisplayTitle( string $page ): void {
		$semanticData = new SemanticData( WikiPage::newFromText( $page ) );
		$semanticData->addPropertyObjectValue( new Property( '_DTITLE' ), new Blob( "$page title" ) );

		$this->getStore()->updateData( $semanticData );
	}

	private function driftStatistics(): void {
		$this->getStore()->getConnection( 'mw.db' )->newUpdateQueryBuilder()
			->update( SQLStore::PROPERTY_STATISTICS_TABLE )
			->set( [ 'usage_count' => 99 ] )
			->where( IDatabase::ALL_ROWS )
			->caller( __METHOD__ )
			->execute();
	}

	/**
	 * @return int[]|null The property's usage count and null count, or null without a statistics row
	 */
	private function fetchCounts( int $propertyId ): ?array {
		$row = $this->getStore()->getConnection( 'mw.db' )->newSelectQueryBuilder()
			->select( [ 'usage_count', 'null_count' ] )
			->from( SQLStore::PROPERTY_STATISTICS_TABLE )
			->where( [ 'p_id' => $propertyId ] )
			->caller( __METHOD__ )
			->fetchRow();

		return $row === false ? null : [ (int)$row->usage_count, (int)$row->null_count ];
	}

	/**
	 * @param int[] $propertyIds
	 */
	private function countPropertiesUsedOnce( array $propertyIds ): int {
		return (int)$this->getStore()->getConnection( 'mw.db' )->newSelectQueryBuilder()
			->select( 'COUNT(*)' )
			->from( SQLStore::PROPERTY_STATISTICS_TABLE )
			->where( [ 'p_id' => $propertyIds, 'usage_count' => 1 ] )
			->caller( __METHOD__ )
			->fetchField();
	}

}
