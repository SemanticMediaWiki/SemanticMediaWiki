<?php

namespace SMW\Tests\Integration\SQLStore;

use DomainException;
use SMW\DataItems\Property;
use SMW\DataItems\WikiPage;
use SMW\DataModel\ContainerSemanticData;
use SMW\DataModel\SemanticData;
use SMW\SQLStore\SQLStore;
use SMW\SQLStore\TableFieldUpdater;
use SMW\Tests\SMWIntegrationTestCase;
use Wikimedia\Rdbms\SelectQueryBuilder;

/**
 * Needs the database: the behaviour is which rows a page update writes before its transaction
 * commits.
 *
 * @covers \SMW\SQLStore\PropertyTableUpdater
 * @covers \SMW\SQLStore\RedirectUpdater
 * @group semantic-mediawiki-integration
 * @group Database
 * @group medium
 *
 * @license GPL-2.0-or-later
 */
class SharedRowsWrittenOnCommitDBIntegrationTest extends SMWIntegrationTestCase {

	public function testUsageCountIsWrittenWhenThePageUpdateCommits(): void {
		$property = new Property( 'Shared_rows_usage_count' );
		$this->storeValue( 'Shared_rows_usage_count_first', $property );
		$countBefore = $this->usageCountOf( $property );

		$countAtCommit = $this->observeAtCommit(
			fn () => $this->storeValue( 'Shared_rows_usage_count_second', $property ),
			fn () => $this->usageCountOf( $property )
		);

		$this->assertSame(
			[ 'at commit' => $countBefore, 'after commit' => $countBefore + 1 ],
			[ 'at commit' => $countAtCommit, 'after commit' => $this->usageCountOf( $property ) ]
		);
	}

	public function testPropertyIsTouchedWhenThePageUpdateCommits(): void {
		$property = new Property( 'Shared_rows_touched' );
		$this->storeValue( 'Shared_rows_touched_first', $property );
		$untouched = $this->markUntouched( $property );

		$touchedAtCommit = $this->observeAtCommit(
			fn () => $this->storeValue( 'Shared_rows_touched_second', $property ),
			fn () => $this->touchedOf( $property )
		);

		$this->assertSame( $untouched, $touchedAtCommit );
		$this->assertNotSame( $untouched, $this->touchedOf( $property ) );
	}

	public function testRedirectUsageCountIsWrittenWhenThePageUpdateCommits(): void {
		$redirect = new Property( '_REDI' );
		$countBefore = $this->usageCountOf( $redirect );

		$countAtCommit = $this->observeAtCommit(
			fn () => $this->storeRedirect( 'Shared_rows_redirect', 'Shared_rows_redirect_target' ),
			fn () => $this->usageCountOf( $redirect )
		);

		$this->assertSame(
			[ 'at commit' => $countBefore, 'after commit' => $countBefore + 1 ],
			[ 'at commit' => $countAtCommit, 'after commit' => $this->usageCountOf( $redirect ) ]
		);
	}

	public function testUsageCountIncludesTheValuesOfSubobjects(): void {
		$property = new Property( 'Shared_rows_subobjects' );
		$this->storeValue( 'Shared_rows_subobjects_first', $property );
		$countBefore = $this->usageCountOf( $property );

		$this->storeValueWithSubobjects( 'Shared_rows_subobjects_second', $property, 'a', 'b' );

		$this->assertSame( $countBefore + 3, $this->usageCountOf( $property ) );
	}

	public function testFailedPageUpdateLeavesUsageCountUnchanged(): void {
		$property = new Property( 'Shared_rows_failed_update' );
		$this->storeValue( 'Shared_rows_failed_update_first', $property );
		$countBefore = $this->usageCountOf( $property );

		$this->failNextPageUpdateBeforeItCommits();
		$this->storeValueIgnoringFailure( 'Shared_rows_failed_update_second', $property );

		$this->assertSame( $countBefore, $this->usageCountOf( $property ) );
	}

	private function storeValue( string $page, Property $property ): void {
		$this->storeValueWithSubobjects( $page, $property );
	}

	private function storeValueWithSubobjects( string $page, Property $property, string ...$subobjectNames ): void {
		$semanticData = new SemanticData( new WikiPage( $page, NS_MAIN ) );
		$semanticData->addPropertyObjectValue( $property, new WikiPage( 'Shared_rows_value', NS_MAIN ) );

		foreach ( $subobjectNames as $subobjectName ) {
			$subobject = new ContainerSemanticData( new WikiPage( $page, NS_MAIN, '', $subobjectName ) );
			$subobject->addPropertyObjectValue( $property, new WikiPage( 'Shared_rows_value', NS_MAIN ) );
			$semanticData->addSubSemanticData( $subobject );
		}

		$this->getStore()->updateData( $semanticData );
	}

	private function storeRedirect( string $page, string $target ): void {
		$semanticData = new SemanticData( new WikiPage( $page, NS_MAIN ) );
		$semanticData->addPropertyObjectValue( new Property( '_REDI' ), new WikiPage( $target, NS_MAIN ) );

		$this->getStore()->updateData( $semanticData );
	}

	private function storeValueIgnoringFailure( string $page, Property $property ): void {
		try {
			$this->storeValue( $page, $property );
		} catch ( DomainException ) {
		}
	}

	/**
	 * The hook runs inside the page update's transaction.
	 */
	private function failNextPageUpdateBeforeItCommits(): void {
		$this->setTemporaryHook(
			'SMW::SQLStore::AfterDataUpdateComplete',
			static function (): void {
				throw new DomainException( 'The page update failed' );
			},
			false
		);
	}

	/**
	 * Runs the page update inside a transaction and returns what $observe sees just before that
	 * transaction commits.
	 */
	private function observeAtCommit( callable $update, callable $observe ): mixed {
		$db = $this->getDb();
		$observed = null;

		$db->startAtomic( __METHOD__ );
		$update();
		$db->onTransactionPreCommitOrIdle( static function () use ( $observe, &$observed ): void {
			$observed = $observe();
		}, __METHOD__ );
		$db->endAtomic( __METHOD__ );

		return $observed;
	}

	private function usageCountOf( Property $property ): int {
		return (int)$this->primarySelect()
			->select( 'usage_count' )
			->from( SQLStore::PROPERTY_STATISTICS_TABLE )
			->where( [ 'p_id' => $this->idOf( $property ) ] )
			->caller( __METHOD__ )
			->fetchField();
	}

	private function touchedOf( Property $property ): string {
		return (string)$this->primarySelect()
			->select( 'smw_touched' )
			->from( SQLStore::ID_TABLE )
			->where( [ 'smw_id' => $this->idOf( $property ) ] )
			->caller( __METHOD__ )
			->fetchField();
	}

	private function markUntouched( Property $property ): string {
		( new TableFieldUpdater( $this->getStore() ) )->updateTouchedField( $this->idOf( $property ), '1970-01-01 00:00:00' );

		return $this->touchedOf( $property );
	}

	private function idOf( Property $property ): int {
		return $this->getStore()->getObjectIds()->getSMWPropertyID( $property );
	}

	/**
	 * The page update's uncommitted writes are visible only on its own connection.
	 */
	private function primarySelect(): SelectQueryBuilder {
		return $this->getStore()->getConnection( 'mw.db' )->newPrimarySelectQueryBuilder();
	}

}
