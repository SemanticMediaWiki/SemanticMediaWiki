<?php

namespace SMW\Tests\Integration\Query;

use SMW\Tests\MwDBaseUnitTestCase;
use SMW\Tests\Utils\UtilityFactory;

use SMW\Query\Language\Description;
use SMW\Query\Language\ConceptDescription;
use SMW\Query\Language\SomeProperty;
use SMW\Query\Language\ThingDescription;
use SMW\Query\Language\ValueDescription;
use SMW\Query\Language\Conjunction;

use SMW\DIWikiPage;
use SMW\DIProperty;
use SMW\DIConcept;

use SMWQuery as Query;

/**
 * @group SMW
 * @group SMWExtension
 *
 * @group semantic-mediawiki-integration
 * @group semantic-mediawiki-query
 *
 * @group mediawiki-database
 * @group medium
 *
 * @license GNU GPL v2+
 * @since 2.1
 *
 * @author mwjames
 */
class ConceptQueryDBIntegrationTest extends MwDBaseUnitTestCase {

	/**
	 * Failed asserting that actual size 0 matches expected size 6.
	 */
	protected $databaseToBeExcluded = array( 'postgres' );

	private $fixturesProvider;
	private $subjects = array();

	private $semanticDataFactory;

	protected function setUp() {
		parent::setUp();

		$this->semanticDataFactory = UtilityFactory::getInstance()->newSemanticDataFactory();

		$this->fixturesProvider = UtilityFactory::getInstance()->newFixturesFactory()->newFixturesProvider();
		$this->fixturesProvider->setupDependencies( $this->getStore() );

		$this->getStore()->updateData(
			$this->fixturesProvider->getFactsheet( 'Berlin' )->asEntity()
		);

		$this->getStore()->updateData(
			$this->fixturesProvider->getFactsheet( 'Paris' )->asEntity()
		);
	}

	protected function tearDown() {

		$fixturesCleaner = UtilityFactory::getInstance()->newFixturesFactory()->newFixturesCleaner();

		$fixturesCleaner
			->purgeAllKnownFacts()
			->purgeSubjects( $this->subjects );

		parent::tearDown();
	}

	/**
	 * @query {{#concept: [[Population::+]] }}
	 */
	public function testConceptQueryForAnyValueCondition() {

		$concept = new DIWikiPage( 'ConceptQueryForAnyValueCondition', SMW_NS_CONCEPT );

		$populationProperty = $this->fixturesProvider->getProperty( 'Population' );

		$description = new SomeProperty(
			$populationProperty,
			new ThingDescription()
		);

		$this->createConceptFor(
			$concept,
			$description,
			'Query for all subjects that contain a population property'
		);

		$this->assertConceptQueryCount(
			6,
			$concept
		);

		$this->getStore()->refreshConceptCache( $concept->getTitle() );

		$this->assertEquals(
			6,
			$this->getStore()->getConceptCacheStatus( $concept )->getCacheCount()
		);
	}

	/**
	 * @query {{#concept: [[Area::SomeDistinctValue]] }}
	 */
	public function testConceptQueryForDistinctValueCondition() {

		$areaValue = $this->fixturesProvider->getFactsheet( 'Berlin' )->getAreaValue();

		$concept = new DIWikiPage( 'ConceptQueryForDistinctValueCondition', SMW_NS_CONCEPT );

		$description = new SomeProperty(
			$areaValue->getProperty(),
			new ValueDescription( $areaValue->getDataItem(), null, SMW_CMP_EQ )
		);

		$this->createConceptFor(
			$concept,
			$description,
			'Query for a specific area value'
		);

		$this->assertConceptQueryCount(
			2,
			$concept
		);

		$this->getStore()->refreshConceptCache( $concept->getTitle() );

		$this->assertEquals(
			2,
			$this->getStore()->getConceptCacheStatus( $concept )->getCacheCount()
		);
	}

	/**
	 * @query {{#concept: [[Concept::ConceptQueryForAnyValueCondition]][[Concept::ConceptQueryForDistinctValueCondition]] }}
	 */
	public function testConceptQueryForConjunctiveCondition() {

		$concept = new DIWikiPage( 'ConceptQueryForConjunctiveCondition', SMW_NS_CONCEPT );

		$description = new Conjunction( array(
			new ConceptDescription( new DIWikiPage( 'ConceptQueryForAnyValueCondition', SMW_NS_CONCEPT ) ),
			new ConceptDescription( new DIWikiPage( 'ConceptQueryForDistinctValueCondition', SMW_NS_CONCEPT ) )
		) );

		$this->createConceptFor(
			$concept,
			$description,
			'Combined concept query'
		);

		$this->assertConceptQueryCount(
			2,
			$concept
		);

		$this->getStore()->refreshConceptCache( $concept->getTitle() );

		$this->assertEquals(
			2,
			$this->getStore()->getConceptCacheStatus( $concept )->getCacheCount()
		);

		$this->subjects = array(
			$concept,
			new DIWikiPage( 'ConceptQueryForAnyValueCondition', SMW_NS_CONCEPT ),
			new DIWikiPage( 'ConceptQueryForDistinctValueCondition', SMW_NS_CONCEPT )
		);
	}

	private function createConceptFor( DIWikiPage $concept, Description $description, $documentation = '' ) {

		$semanticData = $this->semanticDataFactory->setSubject( $concept )->newEmptySemanticData();

		$query = new Query(
			$description,
			false,
			true
		);

		$semanticData->addPropertyObjectValue(
			new DIProperty( '_CONC' ),
			new DIConcept(
				$query->getDescription()->getQueryString(),
				$documentation,
				$query->getDescription()->getQueryFeatures(),
				$query->getDescription()->getSize(),
				$query->getDescription()->getDepth()
			)
		);

		$this->getStore()->updateData( $semanticData );
	}

	private function assertConceptQueryCount( $expected, DIWikiPage $concept ) {

		$description = new ConceptDescription( $concept );

		$query = new Query(
			$description,
			false,
			true
		);

		$query->querymode = Query::MODE_INSTANCES;

		$queryResult = $this->getStore()->getQueryResult( $query );

		$this->assertCount(
			$expected,
			$queryResult->getResults()
		);
	}

}
