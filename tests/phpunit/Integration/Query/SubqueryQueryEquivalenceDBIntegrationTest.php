<?php

namespace SMW\Tests\Integration\Query;

use SMW\DataItems\Number;
use SMW\DataItems\Property;
use SMW\DataItems\WikiPage;
use SMW\Query\Language\Conjunction;
use SMW\Query\Language\Disjunction;
use SMW\Query\Language\SomeProperty;
use SMW\Query\Language\ThingDescription;
use SMW\Query\Language\ValueDescription;
use SMW\Query\Query;
use SMW\Query\QueryResult;
use SMW\SQLStore\QueryEngine\CursorEncoder;
use SMW\Tests\SMWIntegrationTestCase;
use SMW\Tests\Utils\UtilityFactory;

/**
 * Asserts that #ask queries return identical results under both
 * `$smwgQUseLegacyQuery=true` (legacy SELECT DISTINCT path) and
 * `$smwgQUseLegacyQuery=false` (derived-table rewrite via
 * SubqueryQueryBuilder). This is the canary that catches any
 * correctness divergence between the two query paths.
 *
 * @group SMW
 * @group SMWExtension
 *
 * @group semantic-mediawiki-integration
 * @group semantic-mediawiki-query
 *
 * @group mediawiki-database
 * @group Database
 * @group medium
 *
 * @license GPL-2.0-or-later
 * @since 7.0.0
 */
class SubqueryQueryEquivalenceDBIntegrationTest extends SMWIntegrationTestCase {

	private array $subjectsToBeCleared = [];

	private $semanticDataFactory;

	private Property $numberProperty;

	private Property $authorProperty;

	private Property $mixedTieNumberProperty;

	private Property $mixedTieAuthorProperty;

	private WikiPage $alice;

	private WikiPage $bob;

	protected function setUp(): void {
		parent::setUp();

		$this->semanticDataFactory = UtilityFactory::getInstance()->newSemanticDataFactory();

		// Disable result caching so each run re-executes the SQL through
		// the engine (otherwise the second flag value would just hit the
		// cache populated by the first run).
		$this->testEnvironment->addConfiguration( 'smwgQueryResultCacheType', false );

		$this->numberProperty = new Property( 'EquivalenceNumber' );
		$this->numberProperty->setPropertyValueType( '_num' );

		$this->authorProperty = new Property( 'EquivalenceAuthor' );
		$this->authorProperty->setPropertyValueType( '_wpg' );

		// Dedicated property pair for the mixed-direction cursor walk
		// test. Kept separate from `numberProperty`/`authorProperty` so
		// the tied values needed to exercise per-level keyset operator
		// flipping do not bleed into other tests' expected sequences
		// (MariaDB does not promise stable tie ordering across query
		// shapes, so legacy and rewrite paths can disagree on tie
		// order without it being a real divergence).
		$this->mixedTieNumberProperty = new Property( 'EquivalenceMixedTieNumber' );
		$this->mixedTieNumberProperty->setPropertyValueType( '_num' );

		$this->mixedTieAuthorProperty = new Property( 'EquivalenceMixedTieAuthor' );
		$this->mixedTieAuthorProperty->setPropertyValueType( '_wpg' );

		$this->alice = new WikiPage( 'EquivalenceAlice', NS_MAIN );
		$this->bob = new WikiPage( 'EquivalenceBob', NS_MAIN );

		$this->seedFixtures();
	}

	protected function tearDown(): void {
		// Restore the flag to its default before the rest of the suite runs.
		// Goes through testEnvironment for symmetry with runUnderLegacyFlag,
		// keeping the SMW Settings container in sync with $GLOBALS.
		$this->testEnvironment->addConfiguration( 'smwgQUseLegacyQuery', false );

		foreach ( $this->subjectsToBeCleared as $subject ) {
			$this->getStore()->deleteSubject( $subject->getTitle() );
		}

		parent::tearDown();
	}

	public function testSimpleSinglePropertyQuery(): void {
		$factory = function (): Query {
			$description = new SomeProperty(
				$this->numberProperty,
				new ThingDescription()
			);
			$query = new Query( $description );
			$query->querymode = Query::MODE_INSTANCES;
			$query->setUnboundLimit( 50 );
			return $query;
		};

		$this->assertQueryEquivalent( $factory, 'simple single-property' );
	}

	public function testSortedByPropertyValueAscending(): void {
		$factory = function (): Query {
			$description = new SomeProperty(
				$this->numberProperty,
				new ThingDescription()
			);
			$query = new Query( $description );
			$query->querymode = Query::MODE_INSTANCES;
			$query->sort = true;
			$query->sortkeys = [ $this->numberProperty->getKey() => 'ASC' ];
			$query->setUnboundLimit( 50 );
			return $query;
		};

		$this->assertQueryEquivalent( $factory, 'sorted by property value ASC' );
	}

	public function testMultiValuedProperty(): void {
		// Two pages have authors; one has [Alice, Bob], another has [Alice].
		// The result set should contain each page exactly once even though
		// the inner join multiplies rows for the multi-valued page.
		$factory = function (): Query {
			$description = new SomeProperty(
				$this->authorProperty,
				new ThingDescription()
			);
			$query = new Query( $description );
			$query->querymode = Query::MODE_INSTANCES;
			$query->setUnboundLimit( 50 );
			return $query;
		};

		$this->assertQueryEquivalent( $factory, 'multi-valued property' );
	}

	public function testConjunctionAcrossTwoProperties(): void {
		$factory = function (): Query {
			$description = new Conjunction( [
				new SomeProperty(
					$this->numberProperty,
					new ValueDescription( new Number( 15 ), $this->numberProperty, SMW_CMP_EQ )
				),
				new SomeProperty(
					$this->authorProperty,
					new ValueDescription( $this->alice, $this->authorProperty, SMW_CMP_EQ )
				),
			] );
			$query = new Query( $description );
			$query->querymode = Query::MODE_INSTANCES;
			$query->setUnboundLimit( 50 );
			return $query;
		};

		$this->assertQueryEquivalent( $factory, 'conjunction across two properties' );
	}

	public function testCountModeOnMultiValuedProperty(): void {
		$factory = function (): Query {
			$description = new SomeProperty(
				$this->authorProperty,
				new ThingDescription()
			);
			$query = new Query( $description );
			$query->querymode = Query::MODE_COUNT;
			$query->setUnboundLimit( 50 );
			return $query;
		};

		$resultLegacy = $this->runUnderLegacyFlag( true, $factory );
		$resultRewrite = $this->runUnderLegacyFlag( false, $factory );

		$this->assertSame(
			$resultLegacy->getCountValue(),
			$resultRewrite->getCountValue(),
			'Count mismatch between legacy and rewrite for multi-valued property'
		);
	}

	public function testDefaultSortProducesCompoundSortfield(): void {
		// No explicit sortkeys; SMW's OrderCondition defaults to label '#'
		// which produces a comma-joined sortfield value
		// ("t0.smw_sort,t0.smw_title,t0.smw_subobject"). This is the
		// shape almost all real-world #ask queries land on when no
		// explicit sort= parameter is supplied.
		$factory = function (): Query {
			$description = new SomeProperty(
				$this->numberProperty,
				new ThingDescription()
			);
			$query = new Query( $description );
			$query->querymode = Query::MODE_INSTANCES;
			$query->setLimit( 10 );
			// Default sort label '#' produces the compound sortfield
			$query->sortkeys = [ '#' => 'ASC' ];
			return $query;
		};

		$this->assertQueryEquivalent( $factory, 'default-sort compound sortfield' );
	}

	public function testDisjunctionAcrossTwoProperties(): void {
		// Query: [[Has equivalence number::+]] OR [[Has equivalence author::+]].
		// QuerySegmentListProcessor::disjunction populates a temp table
		// that the outer query joins as if it were a normal property
		// table. The rewrite path treats this temp-table join the same
		// as any other property-table join, but the segment shape is
		// distinct enough to warrant explicit equivalence testing.
		$factory = function (): Query {
			$description = new Disjunction( [
				new SomeProperty(
					$this->numberProperty,
					new ThingDescription()
				),
				new SomeProperty(
					$this->authorProperty,
					new ThingDescription()
				),
			] );
			$query = new Query( $description );
			$query->querymode = Query::MODE_INSTANCES;
			$query->setLimit( 20 );
			return $query;
		};

		$this->assertQueryEquivalent( $factory, 'disjunction across two properties' );
	}

	public function testCursorWalkOverDefaultSortMatchesLegacyPath(): void {
		$this->assertCursorWalkEquivalent(
			function ( ?array $cursorPayload ): Query {
				// `SomeProperty[+]` against every page that has any value
				// for the number property, guaranteeing a non-empty
				// instance set so the cursor walk lands on real rows.
				$description = new SomeProperty(
					$this->numberProperty,
					new ThingDescription()
				);
				$query = new Query( $description );
				$query->querymode = Query::MODE_INSTANCES;
				$query->sort = true;
				$query->setUnboundLimit( 2 );
				$query->sortkeys = [ '' => 'ASC' ];
				$query->setCursorAfter( $cursorPayload );
				return $query;
			},
			'default-sort cursor walk'
		);
	}

	public function testCursorWalkOverPropertySortMatchesLegacyPath(): void {
		$this->assertCursorWalkEquivalent(
			function ( ?array $cursorPayload ): Query {
				$description = new SomeProperty(
					$this->numberProperty,
					new ThingDescription()
				);
				$query = new Query( $description );
				$query->querymode = Query::MODE_INSTANCES;
				$query->sort = true;
				$query->setUnboundLimit( 2 );
				$query->sortkeys = [ $this->numberProperty->getKey() => 'ASC' ];
				$query->setCursorAfter( $cursorPayload );
				return $query;
			},
			'property-sort cursor walk'
		);
	}

	public function testCursorWalkDescendingMatchesLegacyPath(): void {
		$this->assertCursorWalkEquivalent(
			function ( ?array $cursorPayload ): Query {
				$description = new SomeProperty(
					$this->numberProperty,
					new ThingDescription()
				);
				$query = new Query( $description );
				$query->querymode = Query::MODE_INSTANCES;
				$query->sort = true;
				$query->setUnboundLimit( 2 );
				$query->sortkeys = [ $this->numberProperty->getKey() => 'DESC' ];
				$query->setCursorAfter( $cursorPayload );
				return $query;
			},
			'property-sort DESC cursor walk'
		);
	}

	public function testCursorWalkMixedDirectionMatchesLegacyPath(): void {
		// Phase 3b-iii: per-level directions across multiple sort keys.
		// The keyset predicate flips its operator at each level
		// independently; the smw_id tiebreak adopts the last level's
		// direction. Walks under legacy and rewrite must produce
		// identical subject sequences.
		$this->assertCursorWalkEquivalent(
			function ( ?array $cursorPayload ): Query {
				$description = new Conjunction( [
					new SomeProperty(
						$this->mixedTieNumberProperty,
						new ThingDescription()
					),
					new SomeProperty(
						$this->mixedTieAuthorProperty,
						new ThingDescription()
					),
				] );
				$query = new Query( $description );
				$query->querymode = Query::MODE_INSTANCES;
				$query->sort = true;
				$query->setUnboundLimit( 1 );
				$query->sortkeys = [
					$this->mixedTieNumberProperty->getKey() => 'ASC',
					$this->mixedTieAuthorProperty->getKey() => 'DESC',
				];
				$query->setCursorAfter( $cursorPayload );
				return $query;
			},
			'mixed-direction cursor walk (asc,desc)'
		);
	}

	public function testCursorRejectedForCompoundHashSortUnderBothPaths(): void {
		// The `#` sort label produces a comma-joined multi-column
		// expression in `$qobj->sortfields`. Cursor mode cannot emit a
		// keyset predicate against a column list and must reject the
		// request explicitly under both query paths. This guards the
		// rejection edge in the subquery path against silent
		// regression as the cursor-emission code evolves.
		$factory = function (): Query {
			$description = new SomeProperty(
				$this->numberProperty,
				new ThingDescription()
			);
			$query = new Query( $description );
			$query->querymode = Query::MODE_INSTANCES;
			$query->sort = true;
			$query->setUnboundLimit( 5 );
			$query->sortkeys = [ '#' => 'ASC' ];
			$query->setCursorAfter( [ 'v' => 1 ] );
			return $query;
		};

		foreach ( [ true, false ] as $legacy ) {
			$this->testEnvironment->addConfiguration( 'smwgQUseLegacyQuery', $legacy );
			$query = $factory();
			$this->getStore()->getQueryResult( $query );
			$errors = $query->getErrors();
			$hasRejection = false;
			foreach ( $errors as $error ) {
				if ( str_contains( (string)$error, 'multi-column sort expression' ) ) {
					$hasRejection = true;
					break;
				}
			}
			$this->assertTrue(
				$hasRejection,
				'Expected cursor mode to reject the `#` compound sort under legacy='
					. ( $legacy ? '1' : '0' ) . '; got errors: ' . print_r( $errors, true )
			);
		}
	}

	/**
	 * Walks the result set page by page in cursor mode, captures the
	 * subject sequence under each path, and asserts the two sequences
	 * match. Bootstrap is `{"v":1}` (no anchor) so the engine takes the
	 * page-1 branch (no predicate); subsequent pages chain the cursor
	 * minted from the previous page.
	 */
	private function assertCursorWalkEquivalent( callable $queryFactory, string $label ): void {
		$walk = function ( bool $legacy ) use ( $queryFactory, $label ): array {
			$this->testEnvironment->addConfiguration( 'smwgQUseLegacyQuery', $legacy );
			$sequence = [];
			$payload = [ 'v' => 1 ];
			// Hard cap on iterations: defensive against an unexpected
			// infinite loop while walking. A correctly-paginating engine
			// terminates by returning a result without a next cursor.
			for ( $i = 0; $i < 10; $i++ ) {
				$query = $queryFactory( $payload );
				$result = $this->getStore()->getQueryResult( $query );
				$this->assertSame(
					[],
					$query->getErrors(),
					"Query errors on iter $i (legacy=" . ( $legacy ? '1' : '0' ) . ") for: $label"
				);
				foreach ( $result->getResults() as $dataItem ) {
					$sequence[] = $dataItem->getHash();
				}
				$nextToken = $result->getNextCursor();
				if ( $nextToken === null ) {
					break;
				}
				$payload = CursorEncoder::decode( $nextToken );
				$this->assertIsArray( $payload, "Decoded cursor payload should be an array under $label" );
			}
			return $sequence;
		};

		$legacySequence = $walk( true );
		$rewriteSequence = $walk( false );

		$this->assertSame(
			$legacySequence,
			$rewriteSequence,
			"Cursor walk sequence mismatch between legacy and rewrite for: $label"
		);
		// Guard against silent empty walks (would falsely satisfy
		// equality). The seed includes pages with the number property,
		// so we expect at least two subjects on every walk.
		$this->assertGreaterThanOrEqual(
			2,
			count( $rewriteSequence ),
			"Cursor walk produced fewer subjects than expected for: $label"
		);
	}

	private function seedFixtures(): void {
		// Three pages with numeric values 10, 20, 30.
		foreach ( [ 10, 20, 30 ] as $value ) {
			$semanticData = $this->semanticDataFactory
				->setTitle( __CLASS__ . '-num-' . $value )
				->newEmptySemanticData();
			$semanticData->addPropertyObjectValue(
				$this->numberProperty,
				new Number( $value )
			);
			$this->getStore()->updateData( $semanticData );
			$this->subjectsToBeCleared[] = $semanticData->getSubject();
		}

		// Page with two authors: Alice and Bob (multi-valued).
		$semanticData = $this->semanticDataFactory
			->setTitle( __CLASS__ . '-multiAuthor' )
			->newEmptySemanticData();
		$semanticData->addPropertyObjectValue( $this->authorProperty, $this->alice );
		$semanticData->addPropertyObjectValue( $this->authorProperty, $this->bob );
		$this->getStore()->updateData( $semanticData );
		$this->subjectsToBeCleared[] = $semanticData->getSubject();

		// Page with a single author: Alice.
		$semanticData = $this->semanticDataFactory
			->setTitle( __CLASS__ . '-singleAuthor' )
			->newEmptySemanticData();
		$semanticData->addPropertyObjectValue( $this->authorProperty, $this->alice );
		$this->getStore()->updateData( $semanticData );
		$this->subjectsToBeCleared[] = $semanticData->getSubject();

		// Page with both: number=15 AND author=Alice (for conjunction).
		$semanticData = $this->semanticDataFactory
			->setTitle( __CLASS__ . '-conjunctionTarget' )
			->newEmptySemanticData();
		$semanticData->addPropertyObjectValue( $this->numberProperty, new Number( 15 ) );
		$semanticData->addPropertyObjectValue( $this->authorProperty, $this->alice );
		$this->getStore()->updateData( $semanticData );
		$this->subjectsToBeCleared[] = $semanticData->getSubject();

		// Pages used by the Phase 3b-iii mixed-direction cursor walk
		// test. They are scoped to `mixedTieNumberProperty` and
		// `mixedTieAuthorProperty` so the tied num/author values stay
		// invisible to other tests' `numberProperty`/`authorProperty`
		// queries. Numbers are tied (5, 5, 7, 7) to force the walk
		// through the second level's DESC operator and then through
		// the smw_id tiebreak.
		$mixedSet = [
			[ 'name' => 'mixedA-num5-aliceBob', 'num' => 5, 'authors' => [ $this->alice, $this->bob ] ],
			[ 'name' => 'mixedB-num5-aliceOnly', 'num' => 5, 'authors' => [ $this->alice ] ],
			[ 'name' => 'mixedC-num7-bobOnly', 'num' => 7, 'authors' => [ $this->bob ] ],
			[ 'name' => 'mixedD-num7-aliceOnly', 'num' => 7, 'authors' => [ $this->alice ] ],
		];
		foreach ( $mixedSet as $fixture ) {
			$semanticData = $this->semanticDataFactory
				->setTitle( __CLASS__ . '-' . $fixture['name'] )
				->newEmptySemanticData();
			$semanticData->addPropertyObjectValue(
				$this->mixedTieNumberProperty,
				new Number( $fixture['num'] )
			);
			foreach ( $fixture['authors'] as $author ) {
				$semanticData->addPropertyObjectValue( $this->mixedTieAuthorProperty, $author );
			}
			$this->getStore()->updateData( $semanticData );
			$this->subjectsToBeCleared[] = $semanticData->getSubject();
		}
	}

	private function runUnderLegacyFlag( bool $legacy, callable $queryFactory ): QueryResult {
		// `addConfiguration` writes to both `$GLOBALS` and the SMW Settings
		// container. `EngineOptions::__construct` reads
		// `$GLOBALS['smwgQUseLegacyQuery']`; a fresh `EngineOptions` is
		// constructed by `QueryEngineFactory::newQueryEngine` on every
		// `getQueryResult` call (via `newSlaveQueryEngine`), so the toggle
		// propagates without needing to clear caches.
		$this->testEnvironment->addConfiguration( 'smwgQUseLegacyQuery', $legacy );

		$query = $queryFactory();
		return $this->getStore()->getQueryResult( $query );
	}

	private function assertQueryEquivalent( callable $queryFactory, string $label ): void {
		$resultLegacy = $this->runUnderLegacyFlag( true, $queryFactory );
		$resultRewrite = $this->runUnderLegacyFlag( false, $queryFactory );

		$this->assertSame(
			$resultLegacy->getCount(),
			$resultRewrite->getCount(),
			"Result count mismatch between legacy and rewrite for: $label"
		);

		$this->assertSame(
			$this->subjectHashes( $resultLegacy ),
			$this->subjectHashes( $resultRewrite ),
			"Result subjects (or order) mismatch between legacy and rewrite for: $label"
		);
	}

	/**
	 * @return string[]
	 */
	private function subjectHashes( QueryResult $result ): array {
		$hashes = [];
		foreach ( $result->getResults() as $dataItem ) {
			$hashes[] = $dataItem->getHash();
		}
		return $hashes;
	}

}
