<?php

namespace SMW\Tests\Integration\Maintenance;

use SMW\DataItems\Property;
use SMW\DataItems\WikiPage;
use SMW\DataModel\SemanticData;
use SMW\SQLStore\EntityStore\FieldList;
use SMW\SQLStore\SQLStore;
use SMW\Tests\SMWIntegrationTestCase;

/**
 * Runs the script against the database, because it reads stored entities and writes their count maps.
 *
 * @covers \SMW\Maintenance\updateEntityCountMap
 * @group semantic-mediawiki
 * @group Database
 * @group medium
 *
 * @license GPL-2.0-or-later
 * @since 3.2
 *
 * @author mwjames
 */
class UpdateEntityCountMapTest extends SMWIntegrationTestCase {

	private $runnerFactory;

	protected function setUp(): void {
		parent::setUp();

		$this->runnerFactory = $this->testEnvironment->getUtilityFactory()->newRunnerFactory();
	}

	public function testCountsTheValuesOfEachProperty(): void {
		$this->storeEntityWithoutCountMap( 'CountMapEntity', [ 'Target A', 'Target B' ] );

		$this->runScript();

		$this->assertSame( [ 2 ], $this->fetchLinkCounts( 'CountMapEntity' ) );
	}

	public function testUpdatesEntitiesBeyondTheFirstBatch(): void {
		$this->storeEntityWithoutCountMap( 'CountMapAlpha', [ 'Target A' ] );
		$this->storeEntityWithoutCountMap( 'CountMapBeta', [ 'Target A' ] );
		$this->storeEntityWithoutCountMap( 'CountMapGamma', [ 'Target A' ] );

		$this->runScript( [ 'batch-size' => 2 ] );

		$this->assertSame( [ 1, 1, 1 ], $this->fetchLinkCounts( 'CountMapAlpha', 'CountMapBeta', 'CountMapGamma' ) );
	}

	public function testUpdatesOnlyTheEntitiesAfterTheStartId(): void {
		$startId = $this->storeEntityWithoutCountMap( 'CountMapSkipped', [ 'Target A' ] );
		$this->storeEntityWithoutCountMap( 'CountMapUpdated', [ 'Target A' ] );

		$this->runScript( [ 's' => $startId ] );

		$this->assertSame( [ null, 1 ], $this->fetchLinkCounts( 'CountMapSkipped', 'CountMapUpdated' ) );
	}

	private function runScript( array $options = [] ): void {
		$maintenanceRunner = $this->runnerFactory->newMaintenanceRunner(
			'\SMW\Maintenance\updateEntityCountMap'
		);

		$maintenanceRunner->setOptions( $options );
		$maintenanceRunner->setQuiet();

		$maintenanceRunner->run();
	}

	/**
	 * Saving the entity writes its count map, so it is removed for the script to restore.
	 *
	 * @param string $page
	 * @param string[] $targets
	 *
	 * @return int The entity's ID
	 */
	private function storeEntityWithoutCountMap( string $page, array $targets ): int {
		$subject = WikiPage::newFromText( $page );
		$property = Property::newFromUserLabel( 'Has count link' );
		$semanticData = new SemanticData( $subject );

		foreach ( $targets as $target ) {
			$semanticData->addPropertyObjectValue( $property, WikiPage::newFromText( $target ) );
		}

		$this->getStore()->updateData( $semanticData );
		$id = $this->getStore()->getObjectIds()->getId( $subject );

		$this->getStore()->getConnection( 'mw.db' )->newUpdateQueryBuilder()
			->update( SQLStore::ID_AUXILIARY_TABLE )
			->set( [ 'smw_countmap' => null ] )
			->where( [ 'smw_id' => $id ] )
			->caller( __METHOD__ )
			->execute();

		return $id;
	}

	/**
	 * @return array<int|null> Each page's number of `Has count link` values, or null without a count map
	 */
	private function fetchLinkCounts( string ...$pages ): array {
		$counts = [];

		foreach ( $pages as $page ) {
			$fieldList = $this->getStore()->getObjectIds()->preload( [ WikiPage::newFromText( $page ) ] );
			$counts[] = $fieldList->getCountListByType( FieldList::PROPERTY_LIST )['Has_count_link'] ?? null;
		}

		return $counts;
	}

}
