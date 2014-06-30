<?php

namespace SMW\Tests\Integration\SPARQLStore;

use SMW\Tests\MwDBaseUnitTestCase;

use SMW\SPARQLStore\SparqlDBConnectionProvider;

use SMW\DIWikiPage;
use SMW\DIProperty;
use SMW\SemanticData;
use SMW\DataValueFactory;

use SMWDIBlob as DIBlob;
use SMWDINumber as DINumber;
use SMWQuery as Query;
use SMWSomeProperty as SomeProperty;
use SMWPrintRequest as PrintRequest;
use SMWPropertyValue as PropertyValue;
use SMWThingDescription as ThingDescription;

use Title;

/**
 * @ingroup Test
 *
 * @group SMW
 * @group SMWExtension
 * @group semantic-mediawiki-integration
 * @group semantic-mediawiki-sparql
 * @group semantic-mediawiki-query
 * @group mediawiki-database
 * @group medium
 *
 * @license GNU GPL v2+
 * @since 1.9.3
 *
 * @author mwjames
 */
class SimpleQueryLookupWithoutBaseStoreDBIntegrationTest extends MwDBaseUnitTestCase {

	private $sparqlDatabase;
	private $subject;
	private $dataValueFactory;

	protected function setUp() {
		parent::setUp();

		if ( !$this->getStore() instanceOf \SMWSparqlStore ) {
			$this->markTestSkipped( "Requires a SMWSparqlStore instance" );
		}

		$this->sparqlDatabase = $this->getStore()->getSparqlDatabase();

		if ( !$this->sparqlDatabase->setConnectionTimeoutInSeconds( 5 )->ping() ) {
			$this->markTestSkipped( "Can't connect to the SparlDatabase" );
		}

		$this->subject = DIWikiPage::newFromTitle( Title::newFromText( __METHOD__ ) );
		$this->dataValueFactory = DataValueFactory::getInstance();
	}

	public function testQueryPropertyBeforeAfterDataRemoval() {

		$property = new DIProperty( 'SomePagePropertyBeforeAfter' );
		$property->setPropertyTypeId( '_wpg' );

		$this->assertEmpty(
			$this->queryResultsForProperty( $property )->getResults()
		);

		$dataItem = DIWikiPage::newFromTitle( Title::newFromText( __METHOD__ ) );

		$this->addTripleToStore(
			$this->subject,
			$property,
			$this->dataValueFactory->newDataItemValue( $dataItem, $property )
		);

		$this->assertThatDataItemIsSet(
			$dataItem,
			$this->queryResultsForProperty( $property )
		);

		$this->assertNotEmpty(
			$this->queryResultsForProperty( $property )->getResults()
		);

		$this->getStore()->clearData( $this->subject );

		$this->assertEmpty(
			$this->queryResultsForProperty( $property )->getResults()
		);
	}

	public function testQueryUserDefinedBlobProperty() {

		$property = new DIProperty( 'SomeBlobProperty' );
		$property->setPropertyTypeId( '_txt' );

		$dataItem = new DIBlob( 'SomePropertyBlobValue' );

		$this->addTripleToStore(
			$this->subject,
			$property,
			$this->dataValueFactory->newDataItemValue( $dataItem, $property )
		);

		$this->assertThatDataItemIsSet(
			$dataItem,
			$this->queryResultsForProperty( $property )
		);
	}

	public function testQueryUserDefinedNumericProperty() {

		$property = new DIProperty( 'SomeNumericProperty' );
		$property->setPropertyTypeId( '_num' );

		$dataItem = new DINumber( 9999 );

		$this->addTripleToStore(
			$this->subject,
			$property,
			$this->dataValueFactory->newDataItemValue( $dataItem, $property )
		);

		$this->assertThatDataItemIsSet(
			$dataItem,
			$this->queryResultsForProperty( $property )
		);
	}

	public function testQueryUserDefinedQuantityProperty() {

		$property = new DIProperty( 'SomeQuantityProperty' );
		$property->setPropertyTypeId( '_qty' );

		$this->addConversionValuesToProperty(
			$property,
			array( '1 km', '1000 m' )
		);

		$dataValue = $this->dataValueFactory->newPropertyObjectValue( $property, '100 km' );

		$this->addTripleToStore(
			$this->subject,
			$property,
			$dataValue
		);

		$this->assertThatDataItemIsSet(
			$dataValue->getDataItem(),
			$this->queryResultsForProperty( $property )
		);
	}

	public function testQueryUserDefinedTemperatureProperty() {

		$property = new DIProperty( 'SomeTemperatureProperty' );
		$property->setPropertyTypeId( '_tem' );

		$dataValue = $this->dataValueFactory->newPropertyObjectValue( $property, '1 °C' );

		$this->addTripleToStore(
			$this->subject,
			$property,
			$dataValue
		);

		$this->assertThatDataItemIsSet(
			$dataValue->getDataItem(),
			$this->queryResultsForProperty( $property )
		);
	}

	public function testQueryCategory() {

		$property = new DIProperty( '_INST' );

		$dataValue = $this->dataValueFactory->newPropertyObjectValue( $property, 'SomeCategory' );

		$this->addTripleToStore(
			$this->subject,
			$property,
			$dataValue
		);

		$this->assertThatDataItemIsSet(
			$dataValue->getDataItem(),
			$this->queryResultsForProperty( $property )
		);
	}

	private function addConversionValuesToProperty( DIProperty $property, array $conversionValues ) {

		$semanticData = new SemanticData( $property->getDiWikiPage() );

		foreach( $conversionValues as $conversionValue ) {
			$semanticData->addDataValue(
				$this->dataValueFactory->newPropertyObjectValue( new DIProperty( '_CONV' ), $conversionValue )
			);
		}

		$this->getStore()->updateData( $semanticData );
	}

	private function addTripleToStore( $subject, $property, $dataValue ) {

		$semanticData = new SemanticData( $subject );
		$semanticData->addDataValue( $dataValue );

		$this->getStore()->updateData( $semanticData );

		$this->assertArrayHasKey(
			$property->getKey(),
			$this->getStore()->getSemanticData( $subject )->getProperties()
		);
	}

	private function queryResultsForProperty( $property ) {

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

		return $this->getStore()->getQueryResult( $query );
	}

	private function assertThatDataItemIsSet( $expectedDataItem, $queryResult ) {

		while ( $resultArray = $queryResult->getNext() ) {
			foreach ( $resultArray as $result ) {
				while ( ( $di = $result->getNextDataItem() ) !== false ) {
					$this->assertEquals( $expectedDataItem, $di );
				}
			}
		}
	}

}
