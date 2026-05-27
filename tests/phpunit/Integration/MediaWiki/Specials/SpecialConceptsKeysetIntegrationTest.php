<?php

namespace SMW\Tests\Integration\MediaWiki\Specials;

use SMW\MediaWiki\Specials\SpecialConcepts;
use SMW\RequestOptions;
use SMW\SQLStore\SQLStore;
use SMW\Tests\SMWIntegrationTestCase;

/**
 * Locks the cursor traversal contract for `SpecialConcepts` (the keyset-aware
 * private `doCursorFetch()` method exercised through the public
 * `fetchFromTable()` shim and the cursor-mode URL flow in `execute()`).
 *
 * Seeds N concept pages directly into `smw_object_ids` + `smw_fpt_conc`
 * (since the production write path for concepts is the
 * `[[Concept:Foo]]` parser-function flow, which is more brittle in tests).
 * Walks the result list forward and backward via cursors and asserts the
 * sequences match an OFFSET control walk over the same data. Includes a
 * deliberate tied-`smw_sort` pair to exercise the predicate's tiebreak path.
 *
 * @group semantic-mediawiki
 * @group Database
 * @group medium
 *
 * @covers \SMW\SQLStore\Lookup\KeysetPaginationTrait
 * @covers \SMW\MediaWiki\Specials\SpecialConcepts
 *
 * @license GPL-2.0-or-later
 * @since 7.0.0
 */
class SpecialConceptsKeysetIntegrationTest extends SMWIntegrationTestCase {

	private const SEED_COUNT = 12;
	private const PAGE_SIZE = 3;
	private const TIED_LOW_INDEX = 11;
	private const TIED_HIGH_INDEX = 12;

	private string $titlePrefix;
	private string $tiedSortValue;

	private array $seededIds = [];

	protected function setUp(): void {
		parent::setUp();

		$runId = bin2hex( random_bytes( 4 ) );
		$this->titlePrefix = 'SpecialConceptsKeysetTest_' . $runId . '_Concept_';
		$this->tiedSortValue = $this->titlePrefix . '02_TIE';

		$store = $this->getStore();
		$db = $store->getConnection( 'mw.db' );

		for ( $i = 1; $i <= self::SEED_COUNT; $i++ ) {
			$title = $this->titleForIndex( $i );
			$sortValue = ( $i === self::TIED_LOW_INDEX || $i === self::TIED_HIGH_INDEX )
				? $this->tiedSortValue
				: $title;

			$db->newInsertQueryBuilder()
				->insertInto( SQLStore::ID_TABLE )
				->row( [
					'smw_namespace' => SMW_NS_CONCEPT,
					'smw_title' => $title,
					'smw_iw' => '',
					'smw_subobject' => '',
					'smw_sortkey' => $title,
					'smw_sort' => $sortValue,
					'smw_proptable_hash' => 'test-hash',
					'smw_hash' => null,
					'smw_rev' => null,
					'smw_touched' => null,
				] )
				->caller( __METHOD__ )
				->execute();

			$smwId = (int)$db->insertId();
			$this->seededIds[] = $smwId;

			$db->newInsertQueryBuilder()
				->insertInto( SQLStore::CONCEPT_TABLE )
				->row( [
					's_id' => $smwId,
					'concept_txt' => '',
					'concept_docu' => '',
					'concept_features' => 1,
					'concept_size' => 0,
					'concept_depth' => 0,
					'cache_date' => null,
					'cache_count' => null,
				] )
				->caller( __METHOD__ )
				->execute();
		}
	}

	protected function tearDown(): void {
		$store = $this->getStore();
		$db = $store->getConnection( 'mw.db' );

		if ( $this->seededIds !== [] ) {
			$db->newDeleteQueryBuilder()
				->deleteFrom( SQLStore::CONCEPT_TABLE )
				->where( [ 's_id' => $this->seededIds ] )
				->caller( __METHOD__ )
				->execute();

			$db->newDeleteQueryBuilder()
				->deleteFrom( SQLStore::ID_TABLE )
				->where( [ 'smw_id' => $this->seededIds ] )
				->caller( __METHOD__ )
				->execute();
		}

		parent::tearDown();
	}

	public function testSeedActuallyContainsATiedPair(): void {
		$store = $this->getStore();
		$offsetSequence = $this->collectOffsetSequence( $store );

		$tiedLow = $this->titleForIndex( self::TIED_LOW_INDEX );
		$tiedHigh = $this->titleForIndex( self::TIED_HIGH_INDEX );

		$lowPos = array_search( $tiedLow, $offsetSequence, true );
		$highPos = array_search( $tiedHigh, $offsetSequence, true );

		$this->assertNotFalse( $lowPos, 'Lower-id tied concept must appear in OFFSET sequence' );
		$this->assertNotFalse( $highPos, 'Higher-id tied concept must appear in OFFSET sequence' );
		$this->assertSame(
			2,
			$lowPos,
			'Lower-id tied concept must sort at position 3 (zero-indexed 2)'
		);
		$this->assertSame(
			3,
			$highPos,
			'Higher-id tied concept must sort at position 4 (zero-indexed 3)'
		);
	}

	public function testForwardCursorTraversalMatchesOffsetControl(): void {
		$store = $this->getStore();
		$offsetSequence = $this->collectOffsetSequence( $store );
		$this->assertNotEmpty( $offsetSequence );

		$cursorSequence = [];
		$cursorAfter = null;
		$guard = 0;

		do {
			$options = $this->buildCursorOptions( $cursorAfter, null );
			$titles = $this->fetchTitlesViaCursor( $options );
			foreach ( $titles as $title ) {
				if ( $this->isTestTitle( $title ) ) {
					$cursorSequence[] = $title;
				}
			}

			$cursorAfter = $options->getLastCursor();
			$guard++;
		} while ( $cursorAfter !== null && $options->getCursorHasMore() && $guard < 1000 );

		$this->assertLessThan( 1000, $guard, 'forward cursor loop did not terminate' );
		$this->assertSame( $offsetSequence, $cursorSequence );
	}

	public function testBackwardCursorTraversalMatchesReversedOffsetControl(): void {
		$store = $this->getStore();
		$offsetSequence = $this->collectOffsetSequence( $store );
		$this->assertNotEmpty( $offsetSequence );

		$lastTitle = end( $offsetSequence );
		$startId = $this->lookupSmwIdForTitle( $store->getConnection( 'mw.db' ), $lastTitle );
		$this->assertNotNull( $startId );

		$cursorSequence = [];
		$cursorBefore = $startId;
		$guard = 0;

		do {
			$options = $this->buildCursorOptions( null, $cursorBefore );
			$titles = $this->fetchTitlesViaCursor( $options );

			$pageTitles = [];
			foreach ( $titles as $title ) {
				if ( $this->isTestTitle( $title ) ) {
					$pageTitles[] = $title;
				}
			}
			foreach ( array_reverse( $pageTitles ) as $title ) {
				array_unshift( $cursorSequence, $title );
			}

			$cursorBefore = $options->getFirstCursor();
			$guard++;
		} while ( $cursorBefore !== null && $options->getCursorHasMore() && $guard < 1000 );

		$this->assertLessThan( 1000, $guard, 'backward cursor loop did not terminate' );

		// setCursorBefore is strict less-than; the start row never appears
		// in any page. Reinstate it for direct comparison with OFFSET.
		$cursorSequence[] = $lastTitle;

		$this->assertSame( $offsetSequence, $cursorSequence );
	}

	public function testForwardCursorOnLowerTiedRowReturnsHigherTiedRow(): void {
		$store = $this->getStore();
		$db = $store->getConnection( 'mw.db' );

		$tiedLow = $this->titleForIndex( self::TIED_LOW_INDEX );
		$tiedHigh = $this->titleForIndex( self::TIED_HIGH_INDEX );

		$lowId = $this->lookupSmwIdForTitle( $db, $tiedLow );
		$this->assertNotNull( $lowId );

		$options = $this->buildCursorOptions( $lowId, null );
		$titles = $this->fetchTitlesViaCursor( $options );

		$pageTitles = array_values( array_filter( $titles, fn ( $t ) => $this->isTestTitle( $t ) ) );
		$this->assertContains(
			$tiedHigh,
			$pageTitles,
			'Forward page from lower-id tied row must include the higher-id tied row via tiebreak'
		);
	}

	public function testBackwardCursorOnHigherTiedRowReturnsLowerTiedRow(): void {
		$store = $this->getStore();
		$db = $store->getConnection( 'mw.db' );

		$tiedLow = $this->titleForIndex( self::TIED_LOW_INDEX );
		$tiedHigh = $this->titleForIndex( self::TIED_HIGH_INDEX );

		$highId = $this->lookupSmwIdForTitle( $db, $tiedHigh );
		$this->assertNotNull( $highId );

		$options = $this->buildCursorOptions( null, $highId );
		$titles = $this->fetchTitlesViaCursor( $options );

		$pageTitles = array_values( array_filter( $titles, fn ( $t ) => $this->isTestTitle( $t ) ) );
		$this->assertContains(
			$tiedLow,
			$pageTitles,
			'Backward page from higher-id tied row must include the lower-id tied row via tiebreak'
		);
	}

	public function testCursorWithNonexistentIdFallsBackToFirstPage(): void {
		$store = $this->getStore();
		$db = $store->getConnection( 'mw.db' );

		$maxRow = $db->newSelectQueryBuilder()
			->select( 'MAX(smw_id) AS max_id' )
			->from( SQLStore::ID_TABLE )
			->caller( __METHOD__ )
			->fetchRow();
		$staleId = (int)$maxRow->max_id + 1_000_000;

		$offsetSequence = $this->collectOffsetSequence( $store );
		$firstPageExpected = array_slice( $offsetSequence, 0, self::PAGE_SIZE );

		$options = $this->buildCursorOptions( $staleId, null );
		$titles = $this->fetchTitlesViaCursor( $options );

		$pageTitles = array_values( array_filter( $titles, fn ( $t ) => $this->isTestTitle( $t ) ) );
		$this->assertSame(
			$firstPageExpected,
			$pageTitles,
			'Stale cursor must fall back to the first page of results'
		);
		$this->assertNotNull(
			$options->getFirstCursor(),
			'firstCursor must be populated from the fallback page'
		);
		$this->assertNotNull(
			$options->getLastCursor(),
			'lastCursor must be populated from the fallback page'
		);
	}

	private function buildCursorOptions( ?int $cursorAfter, ?int $cursorBefore ): RequestOptions {
		$options = new RequestOptions();
		$options->limit = self::PAGE_SIZE;
		$options->setOption( RequestOptions::CURSOR_MODE, true );
		if ( $cursorAfter !== null ) {
			$options->setCursorAfter( $cursorAfter );
		}
		if ( $cursorBefore !== null ) {
			$options->setCursorBefore( $cursorBefore );
		}
		return $options;
	}

	/**
	 * Drive the cursor path via reflection on `doCursorFetch`. Going through
	 * the public `execute()` would mean parsing HTML; the underlying
	 * cursor-aware method is the unit we want to lock down.
	 */
	private function fetchTitlesViaCursor( RequestOptions $options ): array {
		$instance = new SpecialConcepts( $this->getStore() );
		$reflection = new \ReflectionClass( $instance );

		$doFetch = $reflection->getMethod( 'doCursorFetch' );
		$doFetch->setAccessible( true );
		$result = $doFetch->invoke( $instance, $options );

		$titles = [];
		foreach ( $result as $diWikiPage ) {
			$titles[] = $diWikiPage->getDBkey();
		}
		return $titles;
	}

	/**
	 * Oracle that mirrors the SQL filter Special:Concepts uses, plus an
	 * explicit `ORDER BY smw_sort, smw_id` (which the production query is
	 * missing today, see PR description). We can't reuse `fetchFromTable()`
	 * as the oracle because it returns rows in undefined order on MariaDB.
	 */
	private function collectOffsetSequence( $store ): array {
		$db = $store->getConnection( 'mw.db' );
		$rows = $db->newSelectQueryBuilder()
			->select( [ 'smw_id', 'smw_title' ] )
			->tables( [ SQLStore::ID_TABLE, SQLStore::CONCEPT_TABLE ] )
			->joinConds( [ SQLStore::ID_TABLE => [ 'INNER JOIN', [ 'smw_id=s_id' ] ] ] )
			->where( [
				'smw_namespace' => SMW_NS_CONCEPT,
				'smw_iw' => '',
				'smw_subobject' => '',
				'smw_proptable_hash IS NOT NULL',
				'concept_features > 0',
			] )
			->orderBy( [ 'smw_sort', 'smw_id' ] )
			->caller( __METHOD__ )
			->fetchResultSet();

		$titles = [];
		foreach ( $rows as $row ) {
			if ( $this->isTestTitle( $row->smw_title ) ) {
				$titles[] = $row->smw_title;
			}
		}
		return $titles;
	}

	private function lookupSmwIdForTitle( $db, string $title ): ?int {
		$row = $db->newSelectQueryBuilder()
			->select( 'smw_id' )
			->from( SQLStore::ID_TABLE )
			->where( [
				'smw_title' => $title,
				'smw_namespace' => SMW_NS_CONCEPT,
				'smw_iw' => '',
				'smw_subobject' => '',
			] )
			->caller( __METHOD__ )
			->fetchRow();
		return $row ? (int)$row->smw_id : null;
	}

	private function titleForIndex( int $i ): string {
		return $this->titlePrefix . sprintf( '%02d', $i );
	}

	private function isTestTitle( string $title ): bool {
		return str_starts_with( $title, $this->titlePrefix );
	}

}
