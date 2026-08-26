<?php

namespace SMW\Tests\Integration\Query;

use SMW\DataItems\Property;
use SMW\DataItems\WikiPage;
use SMW\DataValueFactory;
use SMW\Query\Language\SomeProperty;
use SMW\Query\Language\ThingDescription;
use SMW\Query\Query;
use SMW\Services\ServicesFactory as ApplicationFactory;
use SMW\Tests\SMWIntegrationTestCase;
use SMW\Tests\TestEnvironment;
use SMW\Tests\Utils\UtilityFactory;

/**
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
 * @since 7.2.2
 */
class QueryResultCacheStatsDBIntegrationTest extends SMWIntegrationTestCase {

	/**
	 * Qualifies the recorded hit so the assertion cannot be satisfied by a
	 * statistic another test left in the shared back-end.
	 */
	private const PROC_CONTEXT = 'QueryResultCacheStatsDBIntegrationTest';

	private ?WikiPage $subject = null;

	protected function setUp(): void {
		parent::setUp();

		$this->testEnvironment->addConfiguration( 'smwgQueryResultCacheType', 'hash' );

		// The shared `SMW.ResultCache` reads the cache settings when it is first
		// resolved, so drop it after changing them.
		ApplicationFactory::clear();
	}

	protected function tearDown(): void {
		if ( $this->subject !== null ) {
			$this->getStore()->deleteSubject( $this->subject->getTitle() );
		}

		ApplicationFactory::clear();

		parent::tearDown();
	}

	/**
	 * A query answered from the cache is counted as a hit by the `ResultCache`
	 * that served it, and written out by `AfterQueryResultLookupComplete`. Both
	 * hooks have to hold the same instance for the count to survive. While they
	 * did not, the query cache statistics on `Special:SemanticMediaWiki`
	 * reported no hits at all, however well the cache was working. See #7102.
	 */
	public function testQueryAnsweredFromCacheIsCountedAsAHit() {
		$property = new Property( 'SomeQueryResultCacheStatsProperty' );
		$property->setPropertyValueType( '_wpg' );

		$semanticData = UtilityFactory::getInstance()->newSemanticDataFactory()->newEmptySemanticData( __METHOD__ );
		$this->subject = $semanticData->getSubject();

		$semanticData->addDataValue(
			DataValueFactory::getInstance()->newDataValueByItem( $this->subject, $property )
		);

		$this->getStore()->updateData( $semanticData );

		// Misses, and schedules the write to the cache back-end.
		$this->getStore()->getQueryResult( $this->newQuery( $property ) );
		TestEnvironment::executePendingDeferredUpdates();

		// Stand in for a second request: a fresh `ResultCache` over the same
		// cache back-end, so the next lookup is answered by the back-end rather
		// than by the in-process result pool, which records no query context.
		ApplicationFactory::clear();

		$this->getStore()->getQueryResult( $this->newQuery( $property ) );
		TestEnvironment::executePendingDeferredUpdates();

		$stats = ApplicationFactory::getInstance()->getResultCache()->getStats();

		$this->assertSame(
			1,
			$stats['hits']['embedded'][self::PROC_CONTEXT] ?? 0
		);
	}

	private function newQuery( Property $property ): Query {
		$query = new Query(
			new SomeProperty( $property, new ThingDescription() )
		);

		$query->querymode = Query::MODE_INSTANCES;
		$query->setLimit( 10 );
		$query->setContextPage( $this->subject );
		$query->setOption( Query::PROC_CONTEXT, self::PROC_CONTEXT );

		return $query;
	}

}
