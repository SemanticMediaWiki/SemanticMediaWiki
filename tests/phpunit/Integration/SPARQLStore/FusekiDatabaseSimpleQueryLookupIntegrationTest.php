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
 * @covers \SMW\SPARQLStore\FusekiHttpDatabaseConnector
 *
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
class FusekiDatabaseSimpleQueryLookupIntegrationTest extends MwDBaseUnitTestCase {

	/** @var SparqlDBConnectionProvider */
	private $connectionProvider;

	/** @var DIWikiPage */
	private $subject;

	/** @var DataValueFactory */
	private $dataValueFactory;

	protected function setUp() {
		parent::setUp();

		$this->connectionProvider = new SparqlDBConnectionProvider( 'Fuseki' );
		$this->subject = DIWikiPage::newFromTitle( Title::newFromText( __METHOD__ ) );
		$this->dataValueFactory = DataValueFactory::getInstance();
	}

	public function testCanConnect() {

		$connection = $this->connectionProvider->getConnection();

		$this->assertInstanceOf(
			'\SMW\SPARQLStore\FusekiHttpDatabaseConnector',
			$connection
		);

		$connection->setConnectionTimeoutInSeconds( 5 );

		if ( !$connection->ping() ) {
			$this->markTestSkipped( 'Fuseki is not accessible' );
		}
	}

	/**
	 * @depends testCanConnect
	 */
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

		$this->assertDataItemEquals(
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

	/**
	 * @depends testCanConnect
	 */
	public function testQueryUserDefinedBlobProperty() {

		$property = new DIProperty( 'SomeBlobProperty' );
		$property->setPropertyTypeId( '_txt' );

		$dataItem = new DIBlob( 'SomePropertyBlobValue' );

		$this->addTripleToStore(
			$this->subject,
			$property,
			$this->dataValueFactory->newDataItemValue( $dataItem, $property )
		);

		$this->assertDataItemEquals(
			$dataItem,
			$this->getQueryResultForProperty( $property )
		);
	}

	/**
	 * @depends testCanConnect
	 */
	public function testQueryUserDefinedNumericProperty() {

		$property = new DIProperty( 'SomeNumericProperty' );
		$property->setPropertyTypeId( '_num' );

		$dataItem = new DINumber( 9999 );

		$this->addTripleToStore(
			$this->subject,
			$property,
			$this->dataValueFactory->newDataItemValue( $dataItem, $property )
		);

		$this->assertDataItemEquals(
			$dataItem,
			$this->getQueryResultForProperty( $property )
		);
	}

	/**
	 * @depends testCanConnect
	 */
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

		$this->assertDataItemEquals(
			$dataValue->getDataItem(),
			$this->getQueryResultForProperty( $property )
		);
	}

	/**
	 * @depends testCanConnect
	 */
	public function testQueryUserDefinedTemperatureProperty() {

		$property = new DIProperty( 'SomeTemperatureProperty' );
		$property->setPropertyTypeId( '_tem' );

		$dataValue = $this->dataValueFactory->newPropertyObjectValue( $property, '1 °C' );

		$this->addTripleToStore(
			$this->subject,
			$property,
			$dataValue
		);

		$this->assertDataItemEquals(
			$dataValue->getDataItem(),
			$this->getQueryResultForProperty( $property )
		);
	}

	/**
	 * @depends testCanConnect
	 */
	public function testQueryCategory() {

		$property = new DIProperty( '_INST' );

		$dataValue = $this->dataValueFactory->newPropertyObjectValue( $property, 'SomeCategory' );

		$this->addTripleToStore(
			$this->subject,
			$property,
			$dataValue
		);

		$this->assertDataItemEquals(
			$dataValue->getDataItem(),
			$this->getQueryResultForProperty( $property )
		);
	}

	private function addConversionValuesToProperty( DIProperty $property, array $conversionValues ) {

		$this->getStore()->setSparqlDatabase( $this->connectionProvider->getConnection() );

		$semanticData = new SemanticData( $property->getDiWikiPage() );

		foreach( $conversionValues as $conversionValue ) {
			$semanticData->addDataValue(
				$this->dataValueFactory->newPropertyObjectValue( new DIProperty( '_CONV' ), $conversionValue )
			);
		}

		$this->getStore()->updateData( $semanticData );
	}

	private function addTripleToStore( $subject, $property, $dataValue ) {

		$this->getStore()->setSparqlDatabase( $this->connectionProvider->getConnection() );

		$semanticData = new SemanticData( $subject );
		$semanticData->addDataValue( $dataValue );

		$this->getStore()->updateData( $semanticData );

		$this->assertArrayHasKey(
			$property->getKey(),
			$this->getStore()->getSemanticData( $subject )->getProperties()
		);
	}

	private function getQueryResultForProperty( $property ) {

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

	private function assertDataItemEquals( $expectedDataItem, $queryResult ) {

		while ( $resultArray = $queryResult->getNext() ) {
			foreach ( $resultArray as $result ) {
				while ( ( $di = $result->getNextDataItem() ) !== false ) {
					$this->assertEquals( $expectedDataItem, $di );
				}
			}
		}
	}

}
