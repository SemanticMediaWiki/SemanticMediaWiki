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
use SMWQueryResult as QueryResult;
use SMWDataValue as DataValue;
use SMWDataItem as DataItem;
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
class QueryResultLookupWithoutBaseStoreDBIntegrationTest extends MwDBaseUnitTestCase {

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
			$this->getQueryResultForProperty( $property )->getResults()
		);

		$dataItem = DIWikiPage::newFromTitle( Title::newFromText( __METHOD__ ) );

		$this->addTripleToStore(
			$this->subject,
			$property,
			$this->dataValueFactory->newDataItemValue( $dataItem, $property )
		);

		$this->assertThatDataItemIsSet(
			$dataItem,
			$this->getQueryResultForProperty( $property )
		);

		$this->assertNotEmpty(
			$this->getQueryResultForProperty( $property )->getResults()
		);

		$this->getStore()->clearData( $this->subject );

		$this->assertEmpty(
			$this->getQueryResultForProperty( $property )->getResults()
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
			$this->getQueryResultForProperty( $property )
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
			$this->getQueryResultForProperty( $property )
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
			$this->getQueryResultForProperty( $property )
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

		$this->assertThatDataValueIsComparable(
			$dataValue,
			$this->getQueryResultForProperty( $property )
		);
	}

	public function testQueryUserDefinedDateProperty() {

		$property = new DIProperty( 'SomeDateProperty' );
		$property->setPropertyTypeId( '_dat' );

		$dataValue = $this->dataValueFactory->newPropertyObjectValue( $property, '1 January 1970' );

		$this->addTripleToStore(
			$this->subject,
			$property,
			$dataValue
		);

		$this->assertThatDataValueIsComparable(
			$dataValue,
			$this->getQueryResultForProperty( $property )
		);
	}

	public function testQueryUserDefinedPropertyForInvalidValueAssignment() {

		$property = new DIProperty( 'SomePropertyWithInvalidValueAssignment' );
		$property->setPropertyTypeId( '_tem' );

		$dataValue = $this->dataValueFactory->newPropertyObjectValue( $property, '1 Jan 1970' );

		$semanticData = new SemanticData( $this->subject );
		$semanticData->addDataValue( $dataValue );

		$this->getStore()->updateData( $semanticData );

		$this->assertEquals(
			0,
			$this->getQueryResultForProperty( $property )->getCount()
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
			$this->getQueryResultForProperty( $property )
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

	private function addTripleToStore( DIWikiPage $subject, DIProperty $property, DataValue $dataValue ) {

		$semanticData = new SemanticData( $subject );
		$semanticData->addDataValue( $dataValue );

		$this->getStore()->updateData( $semanticData );

		$this->assertArrayHasKey(
			$property->getKey(),
			$this->getStore()->getSemanticData( $subject )->getProperties()
		);
	}

	private function getQueryResultForProperty( DIProperty $property ) {

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

	private function assertThatDataItemIsSet( DataItem $expectedDataItem, QueryResult $queryResult ) {

		$this->assertEmpty( $queryResult->getErrors() );

		while ( $resultArray = $queryResult->getNext() ) {
			foreach ( $resultArray as $result ) {
				while ( ( $dataItem = $result->getNextDataItem() ) !== false ) {
					$this->assertEquals( $expectedDataItem, $dataItem );
				}
			}
		}
	}

	private function assertThatDataValueIsComparable( DataValue $expectedDataValue, QueryResult $queryResult ) {

		$this->assertEmpty( $queryResult->getErrors() );

		while ( $resultArray = $queryResult->getNext() ) {
			foreach ( $resultArray as $result ) {
				while ( ( $dataValue = $result->getNextDataValue() ) !== false ) {
					$this->assertEquals( $expectedDataValue->getWikiValue(), $dataValue->getWikiValue() );
				}
			}
		}
	}

}
