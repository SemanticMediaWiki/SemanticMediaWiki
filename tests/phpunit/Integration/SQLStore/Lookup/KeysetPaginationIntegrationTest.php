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
	private const KEY_PREFIX = 'KeysetTest_Property_';

	private array $subjects = [];
	private $semanticDataFactory;
	private $mwHooksHandler;

	protected function setUp(): void {
		parent::setUp();

		$this->semanticDataFactory = UtilityFactory::getInstance()->newSemanticDataFactory();

		$this->mwHooksHandler = UtilityFactory::getInstance()->newMwHooksHandler();
		$this->mwHooksHandler->deregisterListedHooks();

		$store = $this->getStore();

		for ( $i = 1; $i <= self::SEED_COUNT; $i++ ) {
			$propertyKey = sprintf( self::KEY_PREFIX . '%02d', $i );
			$semanticData = $this->semanticDataFactory->newEmptySemanticData( $propertyKey . '_Subject' );
			$this->subjects[] = $semanticData->getSubject();

			$semanticData->addPropertyObjectValue(
				new Property( $propertyKey ),
				new WikiPage( $propertyKey . '_Value', NS_MAIN )
			);

			$store->updateData( $semanticData );
		}
	}

	protected function tearDown(): void {
		$pageDeleter = UtilityFactory::getInstance()->newPageDeleter();
		$pageDeleter->doDeletePoolOfPages( $this->subjects );
		$this->mwHooksHandler->restoreListedHooks();

		parent::tearDown();
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
				if ( str_starts_with( $key, self::KEY_PREFIX ) ) {
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
				if ( str_starts_with( $key, self::KEY_PREFIX ) ) {
					$pageKeys[] = $key;
				}
			}

			foreach ( array_reverse( $pageKeys ) as $key ) {
				array_unshift( $cursorSequence, $key );
			}

			$cursorBefore = $options->getFirstCursor();
			$guard++;
		}

		$cursorSequence[] = $lastKey;

		$this->assertSame(
			$offsetSequence,
			$cursorSequence,
			'Backward cursor traversal plus the start property must reproduce the OFFSET control sequence'
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
				if ( str_starts_with( $key, self::KEY_PREFIX ) ) {
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

}
