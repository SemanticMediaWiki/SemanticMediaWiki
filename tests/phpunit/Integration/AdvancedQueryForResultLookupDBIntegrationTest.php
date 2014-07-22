<?php

namespace SMW\Tests\Integration;

use SMW\Tests\MwDBaseUnitTestCase;
use SMW\Tests\Util\SemanticDataFactory;
use SMW\Tests\Util\QueryResultValidator;

use SMW\DIWikiPage;
use SMW\DIProperty;
use SMW\SemanticData;
use SMW\DataValueFactory;
use SMW\Subobject;

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
use SMWValueDescription as ValueDescription;

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
class AdvancedQueryForResultLookupDBIntegrationTest extends MwDBaseUnitTestCase {

//	protected $destroyDatabaseTablesOnEachRun = true;
	protected $databaseToBeExcluded = array( 'sqlite' );

	private $subjectsToBeCleared = array();
	private $semanticDataFactory;
	private $dataValueFactory;
	private $queryResultValidator;

	protected function setUp() {
		parent::setUp();

		$this->dataValueFactory = DataValueFactory::getInstance();
		$this->semanticDataFactory = new SemanticDataFactory();
		$this->queryResultValidator = new QueryResultValidator();
	}

	protected function tearDown() {

		foreach ( $this->subjectsToBeCleared as $subject ) {
			$this->getStore()->deleteSubject( $subject->getTitle() );
		}

		parent::tearDown();
	}

	/**
	 * {{#ask: [[SomeNumericPropertyToSingleSubject::1111]]
	 *  |?SomeNumericPropertyToSingleSubject
	 * }}
	 */
	public function testQueryToCompareEqualNumericPropertyValuesAssignedToSingleSubject() {

		/**
		 * Arrange
		 */
		$semanticData = $this->semanticDataFactory->setTitle( __METHOD__ )->newEmptySemanticData();

		$expectedDataValueToMatchCondition = $this->newDataValueForNumericPropertyValue(
			'SomeNumericPropertyToSingleSubject',
			1111
		);

		$subobject = new Subobject( $semanticData->getSubject()->getTitle() );
		$subobject->setEmptySemanticDataForId( 'SomeSubobject' );

		$subobject->addDataValue( $expectedDataValueToMatchCondition );

		$semanticData->addPropertyObjectValue(
			$subobject->getProperty(),
			$subobject->getContainer()
		);

		$dataValueWithSamePropertyButDifferentValue = $this->newDataValueForNumericPropertyValue(
			'SomeNumericPropertyToSingleSubject',
			9999
		);

		$semanticData->addDataValue( $dataValueWithSamePropertyButDifferentValue );

		$this->getStore()->updateData( $semanticData );

		/**
		 * Act
		 */
		$queryResult = $this->searchForResultsThatCompareEqualToOnlySingularNumericPropertyValueOf(
			'SomeNumericPropertyToSingleSubject',
			1111
		);

		/**
		 * Assert
		 */
		$this->assertEquals( 1, $queryResult->getCount() );

		$this->queryResultValidator->assertThatQueryResultContains(
			$expectedDataValueToMatchCondition,
			$queryResult
		);

		$expectedSubjects = array(
			$subobject->getSemanticData()->getSubject()
		);

		$this->queryResultValidator->assertThatQueryResultHasSubjects(
			$expectedSubjects,
			$queryResult
		);

		$this->subjectsToBeCleared = array( $semanticData->getSubject() );
	}

	/**
	 * {{#ask: [[SomeNumericPropertyToDifferentSubject::9999]]
	 *  |?SomeNumericPropertyToDifferentSubject
	 * }}
	 */
	public function testQueryToCompareEqualNumericPropertyValuesAssignedToDifferentSubject() {

		/**
		 * Arrange
		 */
		$semanticDataWithSubobject = $this->semanticDataFactory
			->setTitle( __METHOD__ . '-withSobj' )
			->newEmptySemanticData();

		$semanticDataWithoutSubobject = $this->semanticDataFactory
			->setTitle( __METHOD__ . '-wwithoutSobj' )
			->newEmptySemanticData();

		$expectedDataValueToMatchCondition = $this->newDataValueForNumericPropertyValue(
			'SomeNumericPropertyToDifferentSubject',
			9999
		);

		$dataValueWithSamePropertyButDifferentValue = $this->newDataValueForNumericPropertyValue(
			'SomeNumericPropertyToDifferentSubject',
			1111
		);

		$subobject = new Subobject( $semanticDataWithSubobject->getSubject()->getTitle() );
		$subobject->setEmptySemanticDataForId( 'SomeSubobjectToDifferentSubject' );

		$subobject->addDataValue( $expectedDataValueToMatchCondition );

		$semanticDataWithSubobject->addPropertyObjectValue(
			$subobject->getProperty(),
			$subobject->getContainer()
		);

		$semanticDataWithSubobject->addDataValue( $dataValueWithSamePropertyButDifferentValue );
		$semanticDataWithoutSubobject->addDataValue( $expectedDataValueToMatchCondition );

		$this->getStore()->updateData( $semanticDataWithSubobject );
		$this->getStore()->updateData( $semanticDataWithoutSubobject );

		/**
		 * Act
		 */
		$queryResult = $this->searchForResultsThatCompareEqualToOnlySingularNumericPropertyValueOf(
			'SomeNumericPropertyToDifferentSubject',
			9999
		);

		/**
		 * Assert
		 */
		$this->assertEquals( 2, $queryResult->getCount() );

		$this->queryResultValidator->assertThatQueryResultContains(
			$expectedDataValueToMatchCondition,
			$queryResult
		);

		$expectedSubjects = array(
			$subobject->getSemanticData()->getSubject(),
			$semanticDataWithoutSubobject->getSubject()
		);

		$this->queryResultValidator->assertThatQueryResultHasSubjects(
			$expectedSubjects,
			$queryResult
		);

		$this->subjectsToBeCleared = array(
			$semanticDataWithoutSubobject->getSubject(),
			$semanticDataWithSubobject->getSubject()
		);
	}

	private function newDataValueForNumericPropertyValue( $property, $value ) {

		$property = new DIProperty( $property );
		$property->setPropertyTypeId( '_num' );

		$dataItem = new DINumber( $value );

		return $this->dataValueFactory->newDataItemValue(
			$dataItem,
			$property
		);
	}

	private function searchForResultsThatCompareEqualToOnlySingularNumericPropertyValueOf( $property, $value ) {

		$property = new DIProperty( $property );
		$property->setPropertyTypeId( '_num' );

		$dataItem = new DINumber( $value );

		$description = new SomeProperty(
			$property,
			new ValueDescription( $dataItem, null, SMW_CMP_EQ )
		);

		$propertyValue = new PropertyValue( '__pro' );
		$propertyValue->setDataItem( $property );

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
