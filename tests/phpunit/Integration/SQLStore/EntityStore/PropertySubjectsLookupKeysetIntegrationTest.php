<?php

namespace SMW\Tests\Integration\SQLStore\EntityStore;

use SMW\DataItems\Property;
use SMW\DataItems\WikiPage;
use SMW\RequestOptions;
use SMW\Tests\SMWIntegrationTestCase;
use SMW\Tests\Utils\UtilityFactory;

/**
 * Locks the cursor traversal contract for `PropertySubjectsLookup`. Seeds a
 * single property and N subject pages that use it, then walks the resulting
 * subject list forward and backward via `setCursorAfter` / `setCursorBefore`
 * and asserts the sequences match an OFFSET control walk over the same data.
 *
 * Includes a deliberate tied-`smw_sort` pair to exercise the predicate's
 * tiebreak path. Focused tiebreak tests in both directions force the cursor
 * onto the lower/higher tied row directly.
 *
 * @group semantic-mediawiki
 * @group Database
 * @group medium
 *
 * @covers \SMW\SQLStore\Lookup\KeysetPaginationTrait
 * @covers \SMW\SQLStore\EntityStore\PropertySubjectsLookup
 *
 * @license GPL-2.0-or-later
 * @since 7.0.0
 */
class PropertySubjectsLookupKeysetIntegrationTest extends SMWIntegrationTestCase {

	private const SEED_COUNT = 12;
	private const PAGE_SIZE = 3;
	private const TIED_LOW_INDEX = 11;
	private const TIED_HIGH_INDEX = 12;

	private string $subjectPrefix;
	private string $propertyKey;
	private string $tiedSortValue;

	private array $subjects = [];
	private $semanticDataFactory;

	protected function setUp(): void {
		parent::setUp();

		$runId = bin2hex( random_bytes( 4 ) );
		$this->subjectPrefix = 'PropSubjKeysetTest_' . $runId . '_Subject_';
		$this->propertyKey = 'PropSubjKeysetTest_' . $runId . '_Property';
		$this->tiedSortValue = $this->subjectPrefix . '02_TIE';

		$this->semanticDataFactory = UtilityFactory::getInstance()->newSemanticDataFactory();

		$store = $this->getStore();

		$property = new Property( $this->propertyKey );
		for ( $i = 1; $i <= self::SEED_COUNT; $i++ ) {
			$subjectName = $this->subjectNameForIndex( $i );
			$semanticData = $this->semanticDataFactory->newEmptySemanticData( $subjectName );
			$this->subjects[] = $semanticData->getSubject();

			$semanticData->addPropertyObjectValue(
				$property,
				new WikiPage( $subjectName . '_Value', NS_MAIN )
			);

			$store->updateData( $semanticData );
		}

		$this->normaliseSeedSortKeys( $store );
	}

	protected function tearDown(): void {
		$pageDeleter = UtilityFactory::getInstance()->newPageDeleter();
		$pageDeleter->doDeletePoolOfPages( $this->subjects );

		parent::tearDown();
	}

	public function testSeedActuallyContainsATiedPair(): void {
		$store = $this->getStore();
		$offsetSequence = $this->collectOffsetSequence( $store );

		$tiedLow = $this->subjectNameForIndex( self::TIED_LOW_INDEX );
		$tiedHigh = $this->subjectNameForIndex( self::TIED_HIGH_INDEX );

		$lowPos = array_search( $tiedLow, $offsetSequence, true );
		$highPos = array_search( $tiedHigh, $offsetSequence, true );

		$this->assertNotFalse( $lowPos, 'Lower-id tied subject must appear in OFFSET sequence' );
		$this->assertNotFalse( $highPos, 'Higher-id tied subject must appear in OFFSET sequence' );
		$this->assertSame(
			2,
			$lowPos,
			'Lower-id tied subject must sort at position 3 (zero-indexed 2)'
		);
		$this->assertSame(
			3,
			$highPos,
			'Higher-id tied subject must sort at position 4 (zero-indexed 3)'
		);

		$tiedSorts = $this->fetchSortValuesForSubjects( $store, [ $tiedLow, $tiedHigh ] );
		$this->assertCount( 2, $tiedSorts );
		$this->assertSame(
			$tiedSorts[0],
			$tiedSorts[1],
			'Tied pair must share an identical smw_sort value'
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
			$options = $this->buildOptions( $cursorAfter, null );
			$page = $this->fetchSubjects( $store, $options );

			foreach ( $page as $subjectName ) {
				if ( $this->isTestSubject( $subjectName ) ) {
					$cursorSequence[] = $subjectName;
				}
			}

			$cursorAfter = $options->getLastCursor();
			$guard++;
		} while ( $cursorAfter !== null && $options->getCursorHasMore() && $guard < 1000 );

		$this->assertSame( $offsetSequence, $cursorSequence );
	}

	public function testBackwardCursorTraversalMatchesReversedOffsetControl(): void {
		$store = $this->getStore();
		$offsetSequence = $this->collectOffsetSequence( $store );
		$this->assertNotEmpty( $offsetSequence );

		$lastName = end( $offsetSequence );
		$startId = $this->lookupSmwIdForSubject( $store, $lastName );
		$this->assertNotNull( $startId );

		$cursorSequence = [];
		$cursorBefore = $startId;
		$guard = 0;

		do {
			$options = $this->buildOptions( null, $cursorBefore );
			$page = $this->fetchSubjects( $store, $options );

			$pageNames = [];
			foreach ( $page as $subjectName ) {
				if ( $this->isTestSubject( $subjectName ) ) {
					$pageNames[] = $subjectName;
				}
			}
			foreach ( array_reverse( $pageNames ) as $name ) {
				array_unshift( $cursorSequence, $name );
			}

			$cursorBefore = $options->getFirstCursor();
			$guard++;
		} while ( $cursorBefore !== null && $options->getCursorHasMore() && $guard < 1000 );

		// setCursorBefore is strict less-than; the start row never appears
		// in any page. Reinstate it to make the sequences directly comparable.
		$cursorSequence[] = $lastName;

		$this->assertSame( $offsetSequence, $cursorSequence );
	}

	public function testForwardCursorOnLowerTiedRowReturnsHigherTiedRow(): void {
		$store = $this->getStore();
		$tiedLow = $this->subjectNameForIndex( self::TIED_LOW_INDEX );
		$tiedHigh = $this->subjectNameForIndex( self::TIED_HIGH_INDEX );

		$lowId = $this->lookupSmwIdForSubject( $store, $tiedLow );
		$this->assertNotNull( $lowId );

		$options = $this->buildOptions( $lowId, null );
		$page = $this->fetchSubjects( $store, $options );

		$pageNames = array_values( array_filter( $page, fn ( $n ) => $this->isTestSubject( $n ) ) );
		$this->assertContains(
			$tiedHigh,
			$pageNames,
			'Forward page from lower-id tied row must include the higher-id tied row via tiebreak'
		);
	}

	public function testCursorWithNonexistentIdFallsBackToFirstPage(): void {
		$store = $this->getStore();
		$db = $store->getConnection( 'mw.db' );

		// Pick an smw_id well above anything that could plausibly exist in
		// the test DB. The cursor row lookup returns null, so the trait
		// skips the WHERE predicate and the lookup returns the first page
		// in sort order. This is a deliberate UX choice (matches the
		// "forgiving" convention used by Twitter, Reddit, Pinterest, and
		// the existing PR #6564 design): users who hit stale links see
		// content and can navigate from there, rather than landing on a
		// confusing empty "no results" page.
		$maxRow = $db->newSelectQueryBuilder()
			->select( 'MAX(smw_id) AS max_id' )
			->from( 'smw_object_ids' )
			->caller( __METHOD__ )
			->fetchRow();
		$staleId = (int)$maxRow->max_id + 1_000_000;

		$offsetSequence = $this->collectOffsetSequence( $store );
		$firstPageExpected = array_slice( $offsetSequence, 0, self::PAGE_SIZE );

		$options = $this->buildOptions( $staleId, null );
		$page = $this->fetchSubjects( $store, $options );

		$pageNames = array_values( array_filter( $page, fn ( $n ) => $this->isTestSubject( $n ) ) );
		$this->assertSame(
			$firstPageExpected,
			$pageNames,
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

	public function testBackwardCursorOnHigherTiedRowReturnsLowerTiedRow(): void {
		$store = $this->getStore();
		$tiedLow = $this->subjectNameForIndex( self::TIED_LOW_INDEX );
		$tiedHigh = $this->subjectNameForIndex( self::TIED_HIGH_INDEX );

		$highId = $this->lookupSmwIdForSubject( $store, $tiedHigh );
		$this->assertNotNull( $highId );

		$options = $this->buildOptions( null, $highId );
		$page = $this->fetchSubjects( $store, $options );

		$pageNames = array_values( array_filter( $page, fn ( $n ) => $this->isTestSubject( $n ) ) );
		$this->assertContains(
			$tiedLow,
			$pageNames,
			'Backward page from higher-id tied row must include the lower-id tied row via tiebreak'
		);
	}

	private function buildOptions( ?int $cursorAfter, ?int $cursorBefore ): RequestOptions {
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

	private function fetchSubjects( $store, RequestOptions $options ): array {
		$property = new Property( $this->propertyKey );
		$result = $store->getAllPropertySubjects( $property, $options );
		$names = [];
		foreach ( $result as $diWikiPage ) {
			$names[] = $diWikiPage->getDBkey();
		}
		return $names;
	}

	private function collectOffsetSequence( $store ): array {
		$names = [];
		$offset = 0;
		$pageSize = 50;
		$guard = 0;

		while ( $guard < 1000 ) {
			$options = new RequestOptions();
			$options->limit = $pageSize;
			$options->setOffset( $offset );

			$page = $this->fetchSubjects( $store, $options );
			if ( $page === [] ) {
				break;
			}
			foreach ( $page as $name ) {
				if ( $this->isTestSubject( $name ) ) {
					$names[] = $name;
				}
			}
			if ( count( $page ) < $pageSize ) {
				break;
			}
			$offset += $pageSize;
			$guard++;
		}

		return $names;
	}

	private function lookupSmwIdForSubject( $store, string $subjectName ): ?int {
		$db = $store->getConnection( 'mw.db' );
		$row = $db->newSelectQueryBuilder()
			->select( 'smw_id' )
			->from( 'smw_object_ids' )
			->where( [
				'smw_title' => $subjectName,
				'smw_namespace' => NS_MAIN,
				'smw_iw' => '',
				'smw_subobject' => '',
			] )
			->caller( __METHOD__ )
			->fetchRow();
		return $row ? (int)$row->smw_id : null;
	}

	private function normaliseSeedSortKeys( $store ): void {
		$db = $store->getConnection( 'mw.db' );

		for ( $i = 1; $i <= self::SEED_COUNT; $i++ ) {
			$subjectName = $this->subjectNameForIndex( $i );
			$id = $this->lookupSmwIdForSubject( $store, $subjectName );
			$this->assertNotNull(
				$id,
				"Seeded subject at index $i must exist before normalising sort keys"
			);

			$sortValue = ( $i === self::TIED_LOW_INDEX || $i === self::TIED_HIGH_INDEX )
				? $this->tiedSortValue
				: $subjectName;

			$db->newUpdateQueryBuilder()
				->update( 'smw_object_ids' )
				->set( [ 'smw_sort' => $sortValue ] )
				->where( [ 'smw_id' => $id ] )
				->caller( __METHOD__ )
				->execute();
		}
	}

	private function fetchSortValuesForSubjects( $store, array $subjectNames ): array {
		$db = $store->getConnection( 'mw.db' );
		$rows = $db->newSelectQueryBuilder()
			->select( [ 'smw_title', 'smw_sort' ] )
			->from( 'smw_object_ids' )
			->where( [
				'smw_title' => $subjectNames,
				'smw_namespace' => NS_MAIN,
				'smw_iw' => '',
				'smw_subobject' => '',
			] )
			->caller( __METHOD__ )
			->fetchResultSet();

		$sorts = [];
		foreach ( $rows as $row ) {
			$sorts[] = $row->smw_sort;
		}
		return $sorts;
	}

	private function subjectNameForIndex( int $i ): string {
		return $this->subjectPrefix . sprintf( '%02d', $i );
	}

	private function isTestSubject( string $name ): bool {
		return str_starts_with( $name, $this->subjectPrefix );
	}

}
