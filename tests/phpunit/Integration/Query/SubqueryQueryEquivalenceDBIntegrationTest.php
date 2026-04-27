<?php

namespace SMW\Tests\Integration\Query;

use SMW\DataItems\Number;
use SMW\DataItems\Property;
use SMW\DataItems\WikiPage;
use SMW\Query\Language\Conjunction;
use SMW\Query\Language\SomeProperty;
use SMW\Query\Language\ThingDescription;
use SMW\Query\Language\ValueDescription;
use SMW\Query\Query;
use SMW\Query\QueryResult;
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
		$this->numberProperty->setPropertyTypeId( '_num' );

		$this->authorProperty = new Property( 'EquivalenceAuthor' );
		$this->authorProperty->setPropertyTypeId( '_wpg' );

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
