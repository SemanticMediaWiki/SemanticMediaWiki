<?php

namespace SMW\Tests\Integration\Query;

use SMW\Query\Language\Conjunction;
use SMW\Query\Language\NamespaceDescription;
use SMW\Query\Language\SomeProperty;
use SMW\Query\Language\ValueDescription;
use SMW\Tests\SMWIntegrationTestCase;
use SMW\Tests\Utils\UtilityFactory;
use SMWQuery as Query;

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
 * @since 2.1
 *
 * @author mwjames
 */
class NamespaceQueryDBIntegrationTest extends SMWIntegrationTestCase {

	private $fixturesProvider;
	private $semanticDataFactory;

	private $queryResultValidator;
	private $subjects = [];

	protected function setUp(): void {
		parent::setUp();

		$utilityFactory = UtilityFactory::getInstance();
		$utilityFactory->newMwHooksHandler()->invokeHooksFromRegistry();

		$this->semanticDataFactory  = $utilityFactory->newSemanticDataFactory();
		$this->queryResultValidator = $utilityFactory->newValidatorFactory()->newQueryResultValidator();

		$this->fixturesProvider = $utilityFactory->newFixturesFactory()->newFixturesProvider();
		$this->fixturesProvider->setupDependencies( $this->getStore() );
	}

	protected function tearDown(): void {
		$fixturesCleaner = UtilityFactory::getInstance()->newFixturesFactory()->newFixturesCleaner();
		$fixturesCleaner
			->purgeSubjects( $this->subjects )
			->purgeAllKnownFacts();

		parent::tearDown();
	}

	public function testConjunctiveNamespaceQueryThatIncludesSubobject() {
		$semanticData = $this->semanticDataFactory->newEmptySemanticData( __METHOD__ );
		$this->subjects[] = $semanticData->getSubject();

		$factsheet = $this->fixturesProvider->getFactsheet( 'Berlin' );
		$factsheet->setTargetSubject( $semanticData->getSubject() );

		$demographicsSubobject = $factsheet->getDemographics();
		$this->subjects[] = $demographicsSubobject->getSemanticData()->getSubject();

		$semanticData->addPropertyObjectValue(
			$demographicsSubobject->getProperty(),
			$demographicsSubobject->getContainer()
		);

		$populationValue = $factsheet->getPopulationValue();
		$semanticData->addDataValue( $populationValue );

		$this->getStore()->updateData( $semanticData );

		$someProperty = new SomeProperty(
			$populationValue->getProperty(),
			new ValueDescription( $populationValue->getDataItem(), null, SMW_CMP_EQ )
		);

		/**
		 * @query [[Population::SomeDistinctPopulationValue]][[:+]]
		 */
		$description = new Conjunction();
		$description->addDescription( $someProperty );
		$description->addDescription( new NamespaceDescription( NS_MAIN ) );

		$query = new Query(
			$description,
			false,
			false
		);

		$query->querymode = Query::MODE_INSTANCES;

		$queryResult = $this->getStore()->getQueryResult( $query );

		$this->assertEquals(
			2,
			$queryResult->getCount()
		);

		$this->queryResultValidator->assertThatQueryResultHasSubjects(
			$this->subjects,
			$queryResult
		);
	}

}
