<?php

namespace SMW\Tests\Integration\SQLStore\Lookup;

use SMW\DataItems\Property;
use SMW\DataItems\WikiPage;
use SMW\RequestOptions;
use SMW\SQLStore\Lookup\PropertyUsageListLookup;
use SMW\SQLStore\PropertyStatisticsStore;
use SMW\Tests\SMWIntegrationTestCase;
use SMW\Tests\Utils\UtilityFactory;

/**
 * Locks the cursor traversal contract for `KeysetPaginationTrait`. Forward and
 * backward navigation must produce the same property sequence as an OFFSET
 * control walk over the same data. Used to prove that rewriting the cursor
 * predicate from row-constructor form to OR form does not regress behaviour.
 *
 * The seed includes a deliberate tied pair (two properties with identical
 * `smw_sort` values, distinct `smw_id`) placed at the PAGE_SIZE-1 boundary so
 * that the forward cursor walk lands on the lower-id tied row, forcing the
 * predicate's tiebreak path (`smw_sort = ? AND smw_id > ?`) to participate.
 * A focused tiebreak test exercises the backward direction the same way.
 *
 * @group semantic-mediawiki
 * @group Database
 * @group medium
 *
 * @covers \SMW\SQLStore\Lookup\KeysetPaginationTrait
 * @covers \SMW\SQLStore\Lookup\PropertyUsageListLookup
 *
 * @license GPL-2.0-or-later
 * @since 7.0.0
 */
class KeysetPaginationIntegrationTest extends SMWIntegrationTestCase {

	private const SEED_COUNT = 12;
	private const PAGE_SIZE = 3;
	private const TIED_LOW_INDEX = 11;
	private const TIED_HIGH_INDEX = 12;

	/**
	 * Runtime-randomised so concurrent or interrupted test runs cannot pollute
	 * each other's fixture. Stored on the instance so helpers can read it.
	 */
	private string $keyPrefix;

	/**
	 * The shared `smw_sort` value forced onto the tied pair. Crafted to sort
	 * between `${keyPrefix}02` and `${keyPrefix}03` so the tied pair lands at
	 * positions 3 and 4 in the global ASC sort order over the seeded set.
	 */
	private string $tiedSortValue;

	private array $subjects = [];
	private $semanticDataFactory;

	protected function setUp(): void {
		parent::setUp();

		$this->keyPrefix = 'KeysetTest_' . bin2hex( random_bytes( 4 ) ) . '_Property_';
		$this->tiedSortValue = $this->keyPrefix . '02_TIE';

		$this->semanticDataFactory = UtilityFactory::getInstance()->newSemanticDataFactory();

		$store = $this->getStore();

		for ( $i = 1; $i <= self::SEED_COUNT; $i++ ) {
			$propertyKey = $this->propertyKeyForIndex( $i );
			$semanticData = $this->semanticDataFactory->newEmptySemanticData( $propertyKey . '_Subject' );
			$this->subjects[] = $semanticData->getSubject();

			$semanticData->addPropertyObjectValue(
				new Property( $propertyKey ),
				new WikiPage( $propertyKey . '_Value', NS_MAIN )
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

		$tiedLow = $this->propertyKeyForIndex( self::TIED_LOW_INDEX );
		$tiedHigh = $this->propertyKeyForIndex( self::TIED_HIGH_INDEX );

		$lowPos = array_search( $tiedLow, $offsetSequence, true );
		$highPos = array_search( $tiedHigh, $offsetSequence, true );

		$this->assertNotFalse( $lowPos, 'Lower-id tied property must appear in OFFSET sequence' );
		$this->assertNotFalse( $highPos, 'Higher-id tied property must appear in OFFSET sequence' );
		$this->assertSame(
			2,
			$lowPos,
			'Lower-id tied property must sort at position 3 (zero-indexed 2), one short of the PAGE_SIZE=3 boundary'
		);
		$this->assertSame(
			3,
			$highPos,
			'Higher-id tied property must sort at position 4 (zero-indexed 3), the first row of the second page'
		);

		// Guards against the seed silently losing the tie if the schema or
		// SMW write path ever stops accepting our forced smw_sort UPDATE.
		$tiedSorts = $this->fetchSortValuesForKeys( $store, [ $tiedLow, $tiedHigh ] );
		$this->assertCount( 2, $tiedSorts, 'Both tied properties must be in smw_object_ids' );
		$this->assertSame( $tiedSorts[0], $tiedSorts[1], 'Tied pair must share an identical smw_sort value' );
	}

	public function testForwardCursorTraversalMatchesOffsetControl(): void {
		$store = $this->getStore();

		$offsetSequence = $this->collectOffsetSequence( $store );
		$this->assertNotEmpty(
			$offsetSequence,
			'OFFSET control walk must find the seeded properties'
		);

		$cursorSequence = [];
		$cursorAfter = null;
		$guard = 0;

		do {
			$options = new RequestOptions();
			$options->limit = self::PAGE_SIZE;
			if ( $cursorAfter !== null ) {
				$options->setCursorAfter( $cursorAfter );
			}

			$page = $this->fetchListDirect( $store, $options );
			foreach ( $page as $entry ) {
				$key = $entry[0]->getKey();
				if ( $this->isTestKey( $key ) ) {
					$cursorSequence[] = $key;
				}
			}

			$cursorAfter = $options->getLastCursor();
			$guard++;
		} while ( $cursorAfter !== null && $options->getCursorHasMore() && $guard < 1000 );

		$this->assertSame(
			$offsetSequence,
			$cursorSequence,
			'Forward cursor traversal must produce the same filtered sequence as OFFSET control'
		);
	}

	/**
	 * Focused assertion for the forward tiebreak path. The natural traversal
	 * test cannot reliably land the cursor on the lower-id tied row because
	 * the page boundary depends on the count of pre-existing SMW predefined
	 * properties in the test database, which varies. By force-setting the
	 * cursor to that row, we make the tiebreak path participate
	 * deterministically.
	 */
	public function testForwardCursorOnLowerTiedRowReturnsHigherTiedRow(): void {
		$store = $this->getStore();
		$db = $store->getConnection( 'mw.db' );

		$tiedLow = $this->propertyKeyForIndex( self::TIED_LOW_INDEX );
		$tiedHigh = $this->propertyKeyForIndex( self::TIED_HIGH_INDEX );

		$lowId = $this->lookupSmwIdForPropertyKey( $db, $tiedLow );
		$this->assertNotNull( $lowId, 'Lower-id tied property must resolve to an smw_id' );

		$options = new RequestOptions();
		$options->limit = self::PAGE_SIZE;
		$options->setCursorAfter( $lowId );

		$page = $this->fetchListDirect( $store, $options );
		$pageKeys = [];
		foreach ( $page as $entry ) {
			$key = $entry[0]->getKey();
			if ( $this->isTestKey( $key ) ) {
				$pageKeys[] = $key;
			}
		}

		$this->assertContains(
			$tiedHigh,
			$pageKeys,
			'Forward page from lower-id tied row must include the higher-id tied row via the `smw_sort = ? AND smw_id > ?` tiebreak path'
		);
	}

	public function testBackwardCursorTraversalMatchesReversedOffsetControl(): void {
		$store = $this->getStore();

		$offsetSequence = $this->collectOffsetSequence( $store );
		$this->assertNotEmpty(
			$offsetSequence,
			'OFFSET control walk must find the seeded properties'
		);

		$db = $store->getConnection( 'mw.db' );
		$lastKey = end( $offsetSequence );
		$startId = $this->lookupSmwIdForPropertyKey( $db, $lastKey );
		$this->assertNotNull(
			$startId,
			'Last seeded property must have an smw_id from which to seek backward'
		);

		$cursorSequence = [];
		$cursorBefore = $startId;
		$guard = 0;

		while ( $cursorBefore !== null && $guard < 1000 ) {
			$options = new RequestOptions();
			$options->limit = self::PAGE_SIZE;
			$options->setCursorBefore( $cursorBefore );

			$page = $this->fetchListDirect( $store, $options );
			if ( $page === [] ) {
				break;
			}

			$pageKeys = [];
			foreach ( $page as $entry ) {
				$key = $entry[0]->getKey();
				if ( $this->isTestKey( $key ) ) {
					$pageKeys[] = $key;
				}
			}

			foreach ( array_reverse( $pageKeys ) as $key ) {
				array_unshift( $cursorSequence, $key );
			}

			$cursorBefore = $options->getFirstCursor();
			$guard++;
		}

		// `setCursorBefore` is strict less-than: the cursor row itself never
		// appears in any page during a backward walk. The OFFSET control
		// sequence does include that row at the end, so we manually reinstate
		// it to make the two sequences directly comparable.
		$cursorSequence[] = $lastKey;

		$this->assertSame(
			$offsetSequence,
			$cursorSequence,
			'Backward cursor traversal plus the start property must reproduce the OFFSET control sequence'
		);
	}

	/**
	 * Focused assertion for the backward tiebreak path. The forward test
	 * naturally lands the cursor on the lower-id tied row (page 1 ends at
	 * position 3, which is the tied pair's lower-id member), exercising the
	 * forward `smw_sort = ? AND smw_id > ?` clause. The natural backward walk
	 * never lands on the higher-id tied row, so we invoke that case directly.
	 */
	public function testBackwardCursorOnHigherTiedRowReturnsLowerTiedRow(): void {
		$store = $this->getStore();
		$db = $store->getConnection( 'mw.db' );

		$tiedHigh = $this->propertyKeyForIndex( self::TIED_HIGH_INDEX );
		$tiedLow = $this->propertyKeyForIndex( self::TIED_LOW_INDEX );

		$highId = $this->lookupSmwIdForPropertyKey( $db, $tiedHigh );
		$this->assertNotNull( $highId, 'Higher-id tied property must resolve to an smw_id' );

		$options = new RequestOptions();
		$options->limit = self::PAGE_SIZE;
		$options->setCursorBefore( $highId );

		$page = $this->fetchListDirect( $store, $options );
		$pageKeys = [];
		foreach ( $page as $entry ) {
			$key = $entry[0]->getKey();
			if ( $this->isTestKey( $key ) ) {
				$pageKeys[] = $key;
			}
		}

		$this->assertContains(
			$tiedLow,
			$pageKeys,
			'Backward page from higher-id tied row must include the lower-id tied row via the `smw_sort = ? AND smw_id < ?` tiebreak path'
		);
	}

	private function fetchListDirect( $store, RequestOptions $options ): array {
		$lookup = new PropertyUsageListLookup(
			$store,
			new PropertyStatisticsStore( $store->getConnection( 'mw.db' ) ),
			$options
		);
		return $lookup->fetchList();
	}

	/**
	 * Page size here is internal to the OFFSET oracle and need not match
	 * `PAGE_SIZE`; larger pages just enumerate the seeded set faster.
	 */
	private function collectOffsetSequence( $store ): array {
		$keys = [];
		$offset = 0;
		$pageSize = 50;
		$guard = 0;

		while ( $guard < 1000 ) {
			$options = new RequestOptions();
			$options->limit = $pageSize;
			$options->setOffset( $offset );

			$page = $this->fetchListDirect( $store, $options );
			if ( $page === [] ) {
				break;
			}

			foreach ( $page as $entry ) {
				$key = $entry[0]->getKey();
				if ( $this->isTestKey( $key ) ) {
					$keys[] = $key;
				}
			}

			if ( count( $page ) < $pageSize ) {
				break;
			}
			$offset += $pageSize;
			$guard++;
		}

		return $keys;
	}

	private function lookupSmwIdForPropertyKey( $db, string $propertyKey ): ?int {
		$row = $db->newSelectQueryBuilder()
			->select( 'smw_id' )
			->from( 'smw_object_ids' )
			->where( [
				'smw_title' => $propertyKey,
				'smw_namespace' => SMW_NS_PROPERTY,
				'smw_iw' => '',
				'smw_subobject' => '',
			] )
			->caller( __METHOD__ )
			->fetchRow();

		return $row ? (int)$row->smw_id : null;
	}

	/**
	 * Overwrite every seeded property's `smw_sort` with a known raw value,
	 * forcing a tie on the designated pair. SMW's normal write path stores a
	 * collation sort key (not the raw label) via `Collator::getSortKey()`, so
	 * UPDATE-ing only the tied pair would leave them at sort-key bytes that
	 * are not directly comparable with the rest. By rewriting all twelve rows
	 * to predictable raw strings, the resulting `(smw_sort, smw_id)` ordering
	 * matches the test's expectations and remains stable across MW versions
	 * regardless of the active collation.
	 */
	private function normaliseSeedSortKeys( $store ): void {
		$db = $store->getConnection( 'mw.db' );

		for ( $i = 1; $i <= self::SEED_COUNT; $i++ ) {
			$propertyKey = $this->propertyKeyForIndex( $i );
			$id = $this->lookupSmwIdForPropertyKey( $db, $propertyKey );
			$this->assertNotNull(
				$id,
				"Seeded property at index $i must exist before normalising sort keys"
			);

			$sortValue = ( $i === self::TIED_LOW_INDEX || $i === self::TIED_HIGH_INDEX )
				? $this->tiedSortValue
				: $propertyKey;

			$db->newUpdateQueryBuilder()
				->update( 'smw_object_ids' )
				->set( [ 'smw_sort' => $sortValue ] )
				->where( [ 'smw_id' => $id ] )
				->caller( __METHOD__ )
				->execute();
		}
	}

	private function fetchSortValuesForKeys( $store, array $propertyKeys ): array {
		$db = $store->getConnection( 'mw.db' );
		$rows = $db->newSelectQueryBuilder()
			->select( [ 'smw_title', 'smw_sort' ] )
			->from( 'smw_object_ids' )
			->where( [
				'smw_title' => $propertyKeys,
				'smw_namespace' => SMW_NS_PROPERTY,
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

	private function propertyKeyForIndex( int $i ): string {
		return $this->keyPrefix . sprintf( '%02d', $i );
	}

	private function isTestKey( string $key ): bool {
		return str_starts_with( $key, $this->keyPrefix );
	}

}
