<?php

namespace SMW\Tests\Integration\Query;

use SMW\Tests\MwDBaseUnitTestCase;
use SMW\Tests\Util\UtilityFactory;

use SMW\Query\Language\SomeProperty;

use SMWQuery as Query;
use SMWPrintRequest as PrintRequest;
use SMWPropertyValue as PropertyValue;
use SMWExporter as Exporter;

/**
 * @group SMW
 * @group SMWExtension
 *
 * @group semantic-mediawiki-integration
 * @group semantic-mediawiki-query
 *
 * @group semantic-mediawiki-database
 * @group medium
 *
 * @license GNU GPL v2+
 * @since 2.0
 *
 * @author mwjames
 */
class RecordTypeQueryTest extends MwDBaseUnitTestCase {

	private $queryResultValidator;
	private $fixturesProvider;

	protected function setUp() {
		parent::setUp();

		$this->queryResultValidator = UtilityFactory::getInstance()->newValidatorFactory()->newQueryResultValidator();

		$this->fixturesProvider = UtilityFactory::getInstance()->newFixturesFactory()->newFixturesProvider();
		$this->fixturesProvider->setupDependencies( $this->getStore() );
	}

	protected function tearDown() {

		$fixturesCleaner = UtilityFactory::getInstance()->newFixturesFactory()->newFixturesCleaner();
		$fixturesCleaner->purgeAllKnownFacts();

		parent::tearDown();
	}

	public function testSortableRecordQuery() {

		$this->getStore()->updateData(
			$this->fixturesProvider->getFactsheet( 'Berlin' )->asEntity()
		);

		$this->getStore()->updateData(
			$this->fixturesProvider->getFactsheet( 'Paris' )->asEntity()
		);

	//	Exporter::clear();

		$expected = array(
			$this->fixturesProvider->getFactsheet( 'Berlin' )->asSubject(),
			$this->fixturesProvider->getFactsheet( 'Berlin' )->getDemographics()->getSubject()
		);

		/**
		 * PopulationDensity is specified as `_rec`
		 *
		 * @query {{#ask: [[PopulationDensity::SomeDistinctValue]] }}
		 */
		$populationDensityValue = $this->fixturesProvider->getFactsheet( 'Berlin' )->getPopulationDensityValue();

		$description = new SomeProperty(
			$populationDensityValue->getProperty(),
			$populationDensityValue->getQueryDescription( $populationDensityValue->getWikiValue() )
		);

		$propertyValue = new PropertyValue( '__pro' );
		$propertyValue->setDataItem( $populationDensityValue->getProperty() );

		$query = new Query(
			$description,
			false,
			false
		);

		$query->querymode = Query::MODE_INSTANCES;

		$query->sortkeys = array(
			$populationDensityValue->getProperty()->getKey() => 'ASC'
		);

		$query->setLimit( 100 );

		$query->setExtraPrintouts( array(
			new PrintRequest( PrintRequest::PRINT_THIS, '' ),
			new PrintRequest( PrintRequest::PRINT_PROP, null, $propertyValue )
		) );

		$queryResult = $this->getStore()->getQueryResult( $query );

		$this->queryResultValidator->assertThatQueryResultHasSubjects(
			$expected,
			$queryResult
		);
	}

}
