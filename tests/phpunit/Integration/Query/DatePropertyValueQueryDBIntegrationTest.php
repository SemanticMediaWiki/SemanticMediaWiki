<?php

namespace SMW\Tests\Integration\Query;

use SMW\Tests\MwDBaseUnitTestCase;
use SMW\Tests\Utils\UtilityFactory;

use SMW\DIWikiPage;
use SMW\DIProperty;
use SMW\DataValueFactory;

use SMW\Query\Language\SomeProperty;
use SMW\Query\Language\ThingDescription;
use SMW\Query\Language\ValueDescription;

use SMWDIBlob as DIBlob;
use SMWQuery as Query;
use SMWQueryResult as QueryResult;
use SMWDataValue as DataValue;
use SMWDataItem as DataItem;
use SMW\Query\PrintRequest as PrintRequest;
use SMWPropertyValue as PropertyValue;
use SMWExporter as Exporter;

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
 * @since 2.0
 *
 * @author mwjames
 */
class DatePropertyValueQueryDBIntegrationTest extends MwDBaseUnitTestCase {

	private $subjectsToBeCleared = array();
	private $semanticDataFactory;
	private $dataValueFactory;
	private $queryResultValidator;

	protected function setUp() {
		parent::setUp();

		$this->dataValueFactory = DataValueFactory::getInstance();

		$this->semanticDataFactory = UtilityFactory::getInstance()->newSemanticDataFactory();
		$this->queryResultValidator = UtilityFactory::getInstance()->newValidatorFactory()->newQueryResultValidator();

		$this->fixturesProvider = UtilityFactory::getInstance()->newFixturesFactory()->newFixturesProvider();
		$this->fixturesProvider->setupDependencies( $this->getStore() );
	}

	protected function tearDown() {

		$fixturesCleaner = UtilityFactory::getInstance()->newFixturesFactory()->newFixturesCleaner();

		$fixturesCleaner
			->purgeAllKnownFacts()
			->purgeSubjects( $this->subjectsToBeCleared );

		parent::tearDown();
	}

	public function testUserDefinedDateProperty() {

		$property = new DIProperty( 'SomeDateProperty' );
		$property->setPropertyTypeId( '_dat' );

		$dataValue = $this->dataValueFactory->newPropertyObjectValue(
			$property,
			'1 January 1970'
		);

		$semanticData = $this->semanticDataFactory->newEmptySemanticData( __METHOD__ );

		$semanticData->addDataValue( $dataValue );

		$this->getStore()->updateData( $semanticData );

		Exporter::getInstance()->clear();

		$this->assertArrayHasKey(
			$property->getKey(),
			$this->getStore()->getSemanticData( $semanticData->getSubject() )->getProperties()
		);

		$propertyValue = new PropertyValue( '__pro' );
		$propertyValue->setDataItem( $property );

		$description = new SomeProperty(
			$property,
			new ThingDescription()
		);

		$description->addPrintRequest(
			new PrintRequest( PrintRequest::PRINT_PROP, null, $propertyValue )
		);

		$query = new Query(
			$description,
			false,
			false
		);

		$query->querymode = Query::MODE_INSTANCES;

		$queryResult = $this->getStore()->getQueryResult( $query );

		$this->queryResultValidator->assertThatQueryResultContains(
			$dataValue,
			$queryResult
		);

		$this->subjectsToBeCleared[] = $semanticData->getSubject();
	}

	/**
	 * #576
	 */
	public function testSortableDateQuery() {

		$this->getStore()->updateData(
			$this->fixturesProvider->getFactsheet( 'Berlin' )->asEntity()
		);

		// #576 introduced resource caching, therefore make sure that the
		// instance is cleared after data have been created before further
		// tests are carried out
		Exporter::getInstance()->clear();

		/**
		 * @query {{#ask: [[Founded::SomeDistinctValue]] }}
		 */
		$foundedValue = $this->fixturesProvider->getFactsheet( 'Berlin' )->getFoundedValue();

		$description = new SomeProperty(
			$foundedValue->getProperty(),
			new ValueDescription( $foundedValue->getDataItem(), null, SMW_CMP_EQ )
		);

		$propertyValue = new PropertyValue( '__pro' );
		$propertyValue->setDataItem( $foundedValue->getProperty() );

		$query = new Query(
			$description,
			false,
			false
		);

		$query->querymode = Query::MODE_INSTANCES;

		$query->sortkeys = array(
			$foundedValue->getProperty()->getLabel() => 'ASC'
		);

		// Be aware of
		// Virtuoso 22023 Error SR353: Sorted TOP clause specifies more then
		// 10001 rows to sort. Only 10000 are allowed. Either decrease the
		// offset and/or row count or use a scrollable cursor
		$query->setLimit( 100 );

		$query->setExtraPrintouts( array(
			new PrintRequest( PrintRequest::PRINT_THIS, '' ),
			new PrintRequest( PrintRequest::PRINT_PROP, null, $propertyValue )
		) );

		$queryResult = $this->getStore()->getQueryResult( $query );

		$this->queryResultValidator->assertThatQueryResultHasSubjects(
			$this->fixturesProvider->getFactsheet( 'Berlin' )->asSubject(),
			$queryResult
		);
	}

}
