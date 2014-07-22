<?php

namespace SMW\Tests\Integration;

use SMW\Tests\MwDBaseUnitTestCase;
use SMW\Tests\Util\QueryResultValidator;

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
use SMWClassDescription as ClassDescription;

use Title;

/**
 * @ingroup Test
 *
 * @group SMW
 * @group SMWExtension
 * @group semantic-mediawiki-integration
 * @group semantic-mediawiki-query
 * @group mediawiki-database
 * @group medium
 *
 * @license GNU GPL v2+
 * @since 2.0
 *
 * @author mwjames
 */
class SimpleQueryForResultLookupDBIntegrationTest extends MwDBaseUnitTestCase {

//	protected $destroyDatabaseTablesOnEachRun = true;
	protected $databaseToBeExcluded = array( 'sqlite' );

	private $subjectsToBeCleared = array();
	private $subject;
	private $dataValueFactory;
	private $queryResultValidator;

	protected function setUp() {
		parent::setUp();

		$this->subject = DIWikiPage::newFromTitle( Title::newFromText( __METHOD__ ) );
		$this->dataValueFactory = DataValueFactory::getInstance();
		$this->queryResultValidator = new QueryResultValidator();

		$this->subjectsToBeCleared = array( $this->subject );
	}

	protected function tearDown() {

		foreach ( $this->subjectsToBeCleared as $subject ) {
			$this->getStore()->deleteSubject( $subject->getTitle() );
		}

		parent::tearDown();
	}

	public function testQueryPropertyBeforeAfterDataRemoval() {

		$property = new DIProperty( 'SomePagePropertyBeforeAfter' );
		$property->setPropertyTypeId( '_wpg' );

		$this->assertEmpty(
			$this->searchForResultsThatCompareEqualToOnlySingularPropertyOf( $property )->getResults()
		);

		$dataItem = DIWikiPage::newFromTitle( Title::newFromText( __METHOD__ ) );

		$this->addTripleToStore(
			$this->subject,
			$property,
			$this->dataValueFactory->newDataItemValue( $dataItem, $property )
		);

		$this->queryResultValidator->assertThatQueryResultContains(
			$dataItem,
			$this->searchForResultsThatCompareEqualToOnlySingularPropertyOf( $property )
		);

		$this->assertNotEmpty(
			$this->searchForResultsThatCompareEqualToOnlySingularPropertyOf( $property )->getResults()
		);

		$this->getStore()->clearData( $this->subject );

		$this->assertEmpty(
			$this->searchForResultsThatCompareEqualToOnlySingularPropertyOf( $property )->getResults()
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

		$this->queryResultValidator->assertThatQueryResultContains(
			$dataItem,
			$this->searchForResultsThatCompareEqualToOnlySingularPropertyOf( $property )
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

		$this->queryResultValidator->assertThatQueryResultContains(
			$dataItem,
			$this->searchForResultsThatCompareEqualToOnlySingularPropertyOf( $property )
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

		$this->queryResultValidator->assertThatQueryResultContains(
			$dataValue,
			$this->searchForResultsThatCompareEqualToOnlySingularPropertyOf( $property )
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

		$this->queryResultValidator->assertThatQueryResultContains(
			$dataValue,
			$this->searchForResultsThatCompareEqualToOnlySingularPropertyOf( $property )
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

		$this->queryResultValidator->assertThatQueryResultContains(
			$dataValue,
			$this->searchForResultsThatCompareEqualToOnlySingularPropertyOf( $property )
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
			$this->searchForResultsThatCompareEqualToOnlySingularPropertyOf( $property )->getCount()
		);
	}

	public function testQuerySubjects_onCategoryCondition() {

		$property = new DIProperty( '_INST' );

		$dataValue = $this->dataValueFactory->newPropertyObjectValue( $property, 'SomeCategory' );

		$this->addTripleToStore(
			$this->subject,
			$property,
			$dataValue
		);

		$this->queryResultValidator->assertThatQueryResultHasSubjects(
			$this->subject,
			$this->searchForResultsThatCompareEqualToClassOf( 'SomeCategory' )
		);

		$this->queryResultValidator->assertThatQueryResultContains(
			$dataValue,
			$this->searchForResultsThatCompareEqualToClassOf( 'SomeCategory' )
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

	private function searchForResultsThatCompareEqualToOnlySingularPropertyOf( DIProperty $property ) {

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

	private function searchForResultsThatCompareEqualToClassOf( $categoryName ) {

		$propertyValue = new PropertyValue( '__pro' );
		$propertyValue->setDataItem( new DIProperty( '_INST' ) );

		$description = new ClassDescription(
			new DIWikiPage( $categoryName, NS_CATEGORY, '' )
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

}
