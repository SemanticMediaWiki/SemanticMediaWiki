<?php

namespace SMW\Tests\Integration\Query;

use SMW\Tests\MwDBaseUnitTestCase;
use SMW\Tests\Util\SemanticDataFactory;
use SMW\Tests\Util\Validators\QueryResultValidator;

use SMW\Tests\Util\Fixtures\FixturesProvider;

use SMW\Query\Language\NamespaceDescription;
use SMW\Query\Language\Conjunction;
use SMW\Query\Language\SomeProperty;
use SMW\Query\Language\ValueDescription;

use SMWQuery as Query;

/**
 * @group SMW
 * @group SMWExtension
 * @group semantic-mediawiki-integration
 * @group semantic-mediawiki-query
 * @group mediawiki-database
 * @group medium
 *
 * @license GNU GPL v2+
 * @since 2.1
 *
 * @author mwjames
 */
class NamespaceQueryDBIntegrationTest extends MwDBaseUnitTestCase {

	protected $databaseToBeExcluded = array( 'sqlite' );

	private $facts = array();
	private $semanticDataFactory;
	private $queryResultValidator;

	protected function setUp() {
		parent::setUp();

		$this->semanticDataFactory = new SemanticDataFactory();
		$this->queryResultValidator = new QueryResultValidator();

		$fixturesProvider = new FixturesProvider();
		$fixturesProvider->setupDependencies( $this->getStore() );

		$this->facts = $fixturesProvider->getListOfFactsheetInstances();
	}

	protected function tearDown() {

		$fixturesProvider = new FixturesProvider();
		$fixturesProvider->getCleaner()->purgeFacts( $this->facts );

		parent::tearDown();
	}

	public function testConjunctiveNamespaceQueryThatIncludesSubobject() {

		$semanticData = $this->semanticDataFactory->newEmptySemanticData( __METHOD__ );
		$expectedSubjects[] = $semanticData->getSubject();

		$factsheet = $this->facts['berlin'];
		$factsheet->setTargetSubject( $semanticData->getSubject() );

		$demographicsSubobject = $factsheet->getDemographics();
		$expectedSubjects[] = $demographicsSubobject->getSemanticData()->getSubject();

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

		// [[Population::<distinctValueOfFactsheet>]][[:+]]
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
			$expectedSubjects,
			$queryResult
		);
	}

}
