<?php

namespace SMW\Tests\Integration\Maintenance;

use MediaWiki\HookContainer\HookContainer;
use SMW\DataItems\Property;
use SMW\SQLStore\SQLStore;
use SMW\Tests\SMWIntegrationTestCase;

/**
 * Needs the database: the script rewrites the sort fields of the entity table.
 *
 * @covers \SMW\Maintenance\updateEntityCollation
 * @group semantic-mediawiki
 * @group Database
 * @group medium
 *
 * @license GPL-2.0-or-later
 * @since 3.2
 *
 * @author mwjames
 */
class UpdateEntityCollationTest extends SMWIntegrationTestCase {

	private $runnerFactory;
	private $spyMessageReporter;
	private $hookContainer;

	protected function setUp(): void {
		parent::setUp();

		$this->hookContainer = $this->getMockBuilder( HookContainer::class )
			->disableOriginalConstructor()
			->getMock();

		$this->runnerFactory = $this->testEnvironment::getUtilityFactory()->newRunnerFactory();
		$this->spyMessageReporter = $this->testEnvironment::getUtilityFactory()->newSpyMessageReporter();
	}

	public function testRun() {
		$this->runScript();

		$this->assertStringContainsString(
			'Collation update(s)',
			$this->spyMessageReporter->getMessagesAsString()
		);
	}

	public function testPredefinedPropertySortsByItsLabel(): void {
		$id = $this->getStore()->getObjectIds()->getSMWPropertyID( new Property( '_MDAT' ) );
		$this->makeSortValueStale( $id );

		$this->runScript();

		$this->assertSame( [ 'Modification date' ], $this->fetchSortValues( $id ) );
	}

	public function testEntitySortsByItsSortKeyRatherThanItsTitle(): void {
		$id = $this->insertEntity( 'Zebra', 'Aardvark' );

		$this->runScript();

		$this->assertSame( [ 'Aardvark' ], $this->fetchSortValues( $id ) );
	}

	public function testEntityWithoutSortKeySortsByItsTitle(): void {
		$id = $this->insertEntity( 'Untitled_entry', '' );

		$this->runScript();

		$this->assertSame( [ 'Untitled entry' ], $this->fetchSortValues( $id ) );
	}

	public function testUpdatesEntitiesBeyondTheFirstBatch(): void {
		$alpha = $this->insertEntity( 'Alpha', 'Alpha' );
		$beta = $this->insertEntity( 'Beta', 'Beta' );
		$gamma = $this->insertEntity( 'Gamma', 'Gamma' );

		$this->runScript( [ 'batch-size' => 2 ] );

		$this->assertSame( [ 'Alpha', 'Beta', 'Gamma' ], $this->fetchSortValues( $alpha, $beta, $gamma ) );
	}

	public function testSkipsEntitiesUpToTheStartId(): void {
		$skipped = $this->insertEntity( 'Alpha', 'Alpha' );
		$updated = $this->insertEntity( 'Beta', 'Beta' );

		$this->runScript( [ 's' => $skipped ] );

		$this->assertSame( [ 'stale', 'Beta' ], $this->fetchSortValues( $skipped, $updated ) );
	}

	private function runScript( array $options = [] ): void {
		$maintenanceRunner = $this->runnerFactory->newMaintenanceRunner(
			'\SMW\Maintenance\updateEntityCollation'
		);

		$maintenanceRunner->setMessageReporter( $this->spyMessageReporter );
		$maintenanceRunner->setHookContainer( $this->hookContainer );
		$maintenanceRunner->setOptions( $options )->run();
	}

	private function insertEntity( string $title, string $sortKey ): int {
		$connection = $this->getStore()->getConnection( 'mw.db' );

		$connection->newInsertQueryBuilder()
			->insertInto( SQLStore::ID_TABLE )
			->row( [
				'smw_namespace' => NS_MAIN,
				'smw_title' => $title,
				'smw_iw' => '',
				'smw_subobject' => '',
				'smw_sortkey' => $sortKey,
				'smw_sort' => 'stale'
			] )
			->caller( __METHOD__ )
			->execute();

		return $connection->insertId();
	}

	private function makeSortValueStale( int $id ): void {
		$this->getStore()->getConnection( 'mw.db' )->newUpdateQueryBuilder()
			->update( SQLStore::ID_TABLE )
			->set( [ 'smw_sort' => 'stale' ] )
			->where( [ 'smw_id' => $id ] )
			->caller( __METHOD__ )
			->execute();
	}

	/**
	 * @return string[] the sort values in ID order
	 */
	private function fetchSortValues( int ...$ids ): array {
		return $this->getStore()->getConnection( 'mw.db' )->newSelectQueryBuilder()
			->select( 'smw_sort' )
			->from( SQLStore::ID_TABLE )
			->where( [ 'smw_id' => $ids ] )
			->orderBy( 'smw_id' )
			->caller( __METHOD__ )
			->fetchFieldValues();
	}

}
