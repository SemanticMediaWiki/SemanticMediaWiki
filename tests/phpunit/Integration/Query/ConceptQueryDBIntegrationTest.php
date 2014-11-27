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
	 * Fails on postgres on testToCombineDifferentConceptsIntoConjunctiveConceptQuery
	 * Failed asserting that actual size 0 matches expected size 6.
	 */
	protected $databaseToBeExcluded = array( 'postgres' );
	protected $storesToBeExcluded = array( 'SMW\SPARQLStore\SPARQLStore' );

	private $fixturesProvider;
	private $subjectsToBePurged = array();

	private $semanticDataFactory;

	protected function setUp() {
		parent::setUp();

		$this->semanticDataFactory = UtilityFactory::getInstance()->newSemanticDataFactory();

		$this->fixturesProvider = UtilityFactory::getInstance()->newFixturesFactory()->newFixturesProvider();
		$this->fixturesProvider->setupDependencies( $this->getStore() );
	}

	protected function tearDown() {

		$fixturesCleaner = UtilityFactory::getInstance()->newFixturesFactory()->newFixturesCleaner();
		$fixturesCleaner
			->purgeAllKnownFacts()
			->purgeSubjects( $this->subjectsToBePurged );

		parent::tearDown();
	}

	public function testToCombineDifferentConceptsIntoConjunctiveConceptQuery() {

		$this->getStore()->updateData(
			$this->fixturesProvider->getFactsheet( 'Berlin' )->asEntity()
		);

		$this->getStore()->updateData(
			$this->fixturesProvider->getFactsheet( 'Paris' )->asEntity()
		);

		/**
		 * @query {{#concept: [[Population::+]] }}
		 */
		$containsAllPopulationPropertyConcept = new DIWikiPage( 'ContainsAllPopulationProperty', SMW_NS_CONCEPT );

		$populationProperty = $this->fixturesProvider->getProperty( 'Population' );

		$description = new SomeProperty(
			$populationProperty,
			new ThingDescription()
		);

		$this->createConceptFor(
			$containsAllPopulationPropertyConcept,
			$description,
			'Query for all subjects that contain a population property'
		);

		/**
		 * @query {{#concept: [[Area::SomeDistinctValue]] }}
		 */
		$areaValue = $this->fixturesProvider->getFactsheet( 'Berlin' )->getAreaValue();

		$containsOnlySpecificAreaValueConcept = new DIWikiPage( 'ContainsOnlySpecificAreaValue', SMW_NS_CONCEPT );

		$description = new SomeProperty(
			$areaValue->getProperty(),
			new ValueDescription( $areaValue->getDataItem(), null, SMW_CMP_EQ )
		);

		$this->createConceptFor(
			$containsOnlySpecificAreaValueConcept,
			$description,
			'Query for a specific area value'
		);

		/**
		 * @query {{#concept: [[Concept::ContainsAllPopulationProperty]][[Concept::ContainsOnlySpecificAreaValue]] }}
		 */
		$combinedConceptQueryConcept = new DIWikiPage( 'CombinedConceptQuery', SMW_NS_CONCEPT );

		$description = new Conjunction();
		$description->addDescription( new ConceptDescription( $containsAllPopulationPropertyConcept ) );
		$description->addDescription( new ConceptDescription( $containsOnlySpecificAreaValueConcept ) );

		$this->createConceptFor(
			$combinedConceptQueryConcept,
			$description,
			'Combined concept query'
		);

		$this->assertConceptQueryCount(
			6,
			$containsAllPopulationPropertyConcept
		);

		$this->assertConceptQueryCount(
			2,
			$containsOnlySpecificAreaValueConcept
		);

		$this->assertConceptQueryCount(
			2,
			$combinedConceptQueryConcept
		);

		$this->getStore()->refreshConceptCache( $containsAllPopulationPropertyConcept->getTitle() );
		$this->getStore()->refreshConceptCache( $containsOnlySpecificAreaValueConcept->getTitle() );
		$this->getStore()->refreshConceptCache( $combinedConceptQueryConcept->getTitle() );

		$this->assertEquals(
			6,
			$this->getStore()->getConceptCacheStatus( $containsAllPopulationPropertyConcept )->getCacheCount()
		);

		$this->assertEquals(
			2,
			$this->getStore()->getConceptCacheStatus( $containsOnlySpecificAreaValueConcept )->getCacheCount()
		);

		$this->assertEquals(
			2,
			$this->getStore()->getConceptCacheStatus( $combinedConceptQueryConcept )->getCacheCount()
		);
	}

	private function createConceptFor( DIWikiPage $concept, Description $description, $documentation = '' ) {

		$this->subjectsToBePurged[] = $concept;

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
