<?php

namespace SMW\Tests\Integration\Query;

use SMW\Tests\MwDBaseUnitTestCase;
use SMW\Tests\Utils\UtilityFactory;

use SMW\Query\Language\ThingDescription;
use SMW\Query\Language\ValueDescription;
use SMW\Query\Language\SomeProperty;

use SMW\DIWikiPage;
use SMW\DIProperty;
use SMW\DataValueFactory;
use SMW\Subobject;

use SMWQueryParser as QueryParser;
use SMWDINumber as DINumber;
use SMWQuery as Query;
use SMWQueryResult as QueryResult;
use SMWDataValue as DataValue;
use SMWDataItem as DataItem;
use SMW\Query\PrintRequest as PrintRequest;
use SMWPropertyValue as PropertyValue;

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
class NumericPropertyValueQueryDBIntegrationTest extends MwDBaseUnitTestCase {

	private $subjectsToBeCleared = array();
	private $semanticDataFactory;
	private $dataValueFactory;

	private $queryResultValidator;
	private $queryParser;

	protected function setUp() {
		parent::setUp();

		$this->queryResultValidator = UtilityFactory::getInstance()->newValidatorFactory()->newQueryResultValidator();
		$this->semanticDataFactory = UtilityFactory::getInstance()->newSemanticDataFactory();

		$this->dataValueFactory = DataValueFactory::getInstance();
		$this->queryParser = new QueryParser();
	}

	protected function tearDown() {

		foreach ( $this->subjectsToBeCleared as $subject ) {
			$this->getStore()->deleteSubject( $subject->getTitle() );
		}

		parent::tearDown();
	}

	public function testUserDefinedNumericProperty() {

		$dataValue = $this->newDataValueForNumericPropertyValue(
			'SomeNumericProperty',
			9999
		);

		$semanticData = $this->semanticDataFactory->newEmptySemanticData( __METHOD__ );

		$semanticData->addDataValue( $dataValue );

		$this->getStore()->updateData( $semanticData );

		$this->assertArrayHasKey(
			$dataValue->getProperty()->getKey(),
			$this->getStore()->getSemanticData( $semanticData->getSubject() )->getProperties()
		);

		$propertyValue = new PropertyValue( '__pro' );
		$propertyValue->setDataItem( $dataValue->getProperty() );

		$description = new SomeProperty(
			$dataValue->getProperty(),
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

		$this->subjectsToBeCleared = array(
			$semanticData->getSubject()
		);
	}

	/**
	 * {{#ask: [[SomeNumericPropertyToSingleSubject::1111]]
	 *  |?SomeNumericPropertyToSingleSubject
	 * }}
	 */
	public function testQueryToCompareEqualNumericPropertyValuesAssignedToSingleSubject() {

		$semanticData = $this->semanticDataFactory->setTitle( __METHOD__ )->newEmptySemanticData();

		$expectedDataValueToMatchCondition = $this->newDataValueForNumericPropertyValue(
			'SomeNumericPropertyToSingleSubject',
			1111
		);

		$subobject = new Subobject( $semanticData->getSubject()->getTitle() );
		$subobject->setEmptyContainerForId( 'SomeSubobject' );

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

		$property = new DIProperty( 'SomeNumericPropertyToSingleSubject' );
		$property->setPropertyTypeId( '_num' );

		$dataItem = new DINumber( 1111 );

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

		$queryResult = $this->getStore()->getQueryResult( $query );

		$this->assertEquals(
			1,
			$queryResult->getCount()
		);

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

		$this->subjectsToBeCleared = array(
			$semanticData->getSubject()
		);
	}

	/**
	 * {{#ask: [[SomeNumericPropertyToDifferentSubject::9999]]
	 *  |?SomeNumericPropertyToDifferentSubject
	 * }}
	 */
	public function testQueryToCompareEqualNumericPropertyValuesAssignedToDifferentSubject() {

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
		$subobject->setEmptyContainerForId( 'SomeSubobjectToDifferentSubject' );

		$subobject->addDataValue( $expectedDataValueToMatchCondition );

		$semanticDataWithSubobject->addPropertyObjectValue(
			$subobject->getProperty(),
			$subobject->getContainer()
		);

		$semanticDataWithSubobject->addDataValue( $dataValueWithSamePropertyButDifferentValue );
		$semanticDataWithoutSubobject->addDataValue( $expectedDataValueToMatchCondition );

		$this->getStore()->updateData( $semanticDataWithSubobject );
		$this->getStore()->updateData( $semanticDataWithoutSubobject );

		$property = new DIProperty( 'SomeNumericPropertyToDifferentSubject' );
		$property->setPropertyTypeId( '_num' );

		$dataItem = new DINumber( 9999 );

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

		$queryResult = $this->getStore()->getQueryResult( $query );

		$this->assertEquals(
			2,
			$queryResult->getCount()
		);

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

}
