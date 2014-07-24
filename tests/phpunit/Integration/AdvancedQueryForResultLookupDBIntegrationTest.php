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

use SMWQueryParser as QueryParser;
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
use SMWConjunction as Conjunction;
use SMWDisjunction as Disjunction;
use SMWClassDescription as ClassDescription;

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

	protected $databaseToBeExcluded = array( 'sqlite' );

	private $subjectsToBeCleared = array();
	private $semanticDataFactory;
	private $dataValueFactory;
	private $queryResultValidator;
	private $queryParser;

	protected function setUp() {
		parent::setUp();

		$this->dataValueFactory = DataValueFactory::getInstance();
		$this->semanticDataFactory = new SemanticDataFactory();
		$this->queryResultValidator = new QueryResultValidator();
		$this->queryParser = new QueryParser();

	//	$this->getStore()->getSparqlDatabase()->deleteAll();
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

		$queryResult = $this->searchForResultsThatCompareEqualToOnlySingularNumericPropertyValueOf(
			'SomeNumericPropertyToSingleSubject',
			1111
		);

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

		$queryResult = $this->searchForResultsThatCompareEqualToOnlySingularNumericPropertyValueOf(
			'SomeNumericPropertyToDifferentSubject',
			9999
		);

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

	private function newDataValueForPagePropertyValue( $property, $value ) {

		$property = new DIProperty( $property );
		$property->setPropertyTypeId( '_wpg' );

		$dataItem = new DIWikiPage( $value, NS_MAIN, '' );

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

	/**
	 * {{#ask: [[LocatedIn.MemberOf::Wonderland]] }}
	 * {{#ask: [[LocatedIn::<q>[[MemberOf::Wonderland]]</q>]] }}
	 */
	public function testPropertyChainAsSubqueryThatComparesEqualToSpecifiedValue() {

		/**
		 * Page ...-dreamland annotated with [[LocatedIn::BananaWonderland]]
		 */
		$semanticDataOfDreamland = $this->semanticDataFactory
			->setTitle( __METHOD__ . '-dreamland' )
			->newEmptySemanticData();

		$semanticDataOfDreamland->addDataValue(
			$this->newDataValueForPagePropertyValue( 'LocatedIn', 'BananaWonderland' )
		);

		/**
		 * Page BananaWonderland annotated with [[MemberOf::Wonderland]]
		 */
		$semanticDataOfWonderland = $this->semanticDataFactory
			->setTitle( 'BananaWonderland' )
			->newEmptySemanticData();

		$semanticDataOfWonderland->addDataValue(
			$this->newDataValueForPagePropertyValue( 'MemberOf', 'Wonderland' )
		);

		$this->getStore()->updateData( $semanticDataOfDreamland );
		$this->getStore()->updateData( $semanticDataOfWonderland );

		$description = new SomeProperty(
			DIProperty::newFromUserLabel( 'LocatedIn' )->setPropertyTypeId( '_wpg' ),
			new SomeProperty(
				DIProperty::newFromUserLabel( 'MemberOf' )->setPropertyTypeId( '_wpg' ),
				new ValueDescription(
					new DIWikiPage( 'Wonderland', NS_MAIN, '' ),
					DIProperty::newFromUserLabel( 'MemberOf' )->setPropertyTypeId( '_wpg' ), SMW_CMP_EQ
				)
			)
		);

		$this->assertEquals(
			$description,
			$this->queryParser->getQueryDescription( '[[LocatedIn.MemberOf::Wonderland]]' )
		);

		$this->assertEquals(
			$description,
			$this->queryParser->getQueryDescription( '[[LocatedIn::<q>[[MemberOf::Wonderland]]</q>]]' )
		);

		$query = new Query(
			$description,
			false,
			false
		);

		$query->querymode = Query::MODE_INSTANCES;

		$queryResult = $this->getStore()->getQueryResult( $query );

		$expectedSubjects = array(
			$semanticDataOfDreamland->getSubject()
		);

		$this->assertEquals(
			1,
			$queryResult->getCount()
		);

		$this->queryResultValidator->assertThatQueryResultHasSubjects(
			$expectedSubjects,
			$queryResult
		);

		$this->subjectsToBeCleared = array(
			$semanticDataOfWonderland->getSubject(),
			$semanticDataOfDreamland->getSubject()
		);
	}

	/**
	 * {{#ask: [[Category:HappyPlaces]] [[LocatedIn.MemberOf::Wonderland]] }}
	 */
	public function testConjunctionForCategoryAndPropertyChainSubqueryThatComparesEqualToSpecifiedValue() {

		/**
		 * Page ...-neverland annotated with [[LocatedIn::BananaWonderland]]
		 */
		$semanticDataOfNeverland = $this->semanticDataFactory
			->setTitle( __METHOD__ . '-neverland' )
			->newEmptySemanticData();

		$semanticDataOfNeverland->addDataValue(
			$this->newDataValueForPagePropertyValue( 'LocatedIn', 'BananaWonderland' )
		);

		/**
		 * Page ...-dreamland annotated with [[Category:HappyPlaces]] [[LocatedIn::BananaWonderland]]
		 */
		$semanticDataOfDreamland = $this->semanticDataFactory
			->setTitle( __METHOD__ . '-dreamland' )
			->newEmptySemanticData();

		$semanticDataOfDreamland->addDataValue(
			$this->newDataValueForPagePropertyValue( 'LocatedIn', 'BananaWonderland' )
		);

		$semanticDataOfDreamland->addDataValue(
			$this->dataValueFactory->newPropertyObjectValue( new DIProperty( '_INST' ), 'HappyPlaces' )
		);

		/**
		 * Page BananaWonderland annotated with [[MemberOf::Wonderland]]
		 */
		$semanticDataOfWonderland = $this->semanticDataFactory
			->setTitle( 'BananaWonderland' )
			->newEmptySemanticData();

		$semanticDataOfWonderland->addDataValue(
			$this->newDataValueForPagePropertyValue( 'MemberOf', 'Wonderland' )
		);

		$this->getStore()->updateData( $semanticDataOfDreamland );
		$this->getStore()->updateData( $semanticDataOfWonderland );
		$this->getStore()->updateData( $semanticDataOfNeverland );

		$someProperty = new SomeProperty(
			DIProperty::newFromUserLabel( 'LocatedIn' )->setPropertyTypeId( '_wpg' ),
			new SomeProperty(
				DIProperty::newFromUserLabel( 'MemberOf' )->setPropertyTypeId( '_wpg' ),
				new ValueDescription(
					new DIWikiPage( 'Wonderland', NS_MAIN, '' ),
					DIProperty::newFromUserLabel( 'MemberOf' )->setPropertyTypeId( '_wpg' ), SMW_CMP_EQ
				)
			)
		);

		$classDescription = new ClassDescription(
			new DIWikiPage( 'HappyPlaces', NS_CATEGORY, '' )
		);

		$description = new Conjunction();
		$description->addDescription( $classDescription );
		$description->addDescription( $someProperty );

		$this->assertEquals(
			$description,
			$this->queryParser->getQueryDescription( '[[Category:HappyPlaces]] [[LocatedIn.MemberOf::Wonderland]]' )
		);

		$this->assertEquals(
			$description,
			$this->queryParser->getQueryDescription( '[[Category:HappyPlaces]] [[LocatedIn::<q>[[MemberOf::Wonderland]]</q>]]' )
		);

		$query = new Query(
			$description,
			false,
			false
		);

		$query->querymode = Query::MODE_INSTANCES;

		$queryResult = $this->getStore()->getQueryResult( $query );

		$expectedSubjects = array(
			$semanticDataOfDreamland->getSubject()
		);

		$this->assertEquals(
			1,
			$queryResult->getCount()
		);

		$this->queryResultValidator->assertThatQueryResultHasSubjects(
			$expectedSubjects,
			$queryResult
		);

		$this->subjectsToBeCleared = array(
			$semanticDataOfWonderland->getSubject(),
			$semanticDataOfDreamland->getSubject(),
			$semanticDataOfNeverland->getSubject()
		);
	}

	/**
	 * {{#ask: [[Category:WickedPlaces]] OR [[LocatedIn.MemberOf::Wonderland]] }}
	 */
	public function testDisjunctionSubqueryForPageTypePropertyChainThatComparesEqualToValue() {

		if ( $this->getDBConnection()->getType() == 'postgres' ) {
			$this->markTestSkipped( "Issue with postgres + Disjunction, for details see #454" );
		}

		/**
		 * Page ...-dangerland annotated with [[Category:WickedPlaces]]
		 */
		$semanticDataOfDangerland = $this->semanticDataFactory
			->setTitle( __METHOD__ . '-dangerland' )
			->newEmptySemanticData();

		$semanticDataOfDangerland->addDataValue(
			$this->dataValueFactory->newPropertyObjectValue( new DIProperty( '_INST' ), 'WickedPlaces' )
		);

		/**
		 * Page ...-dreamland annotated with [[LocatedIn::BananaWonderland]]
		 */
		$semanticDataOfDreamland = $this->semanticDataFactory
			->setTitle( __METHOD__ . '-dreamland' )
			->newEmptySemanticData();

		$semanticDataOfDreamland->addDataValue(
			$this->newDataValueForPagePropertyValue( 'LocatedIn', 'BananaWonderland' )
		);

		/**
		 * Page BananaWonderland annotated with [[MemberOf::Wonderland]]
		 */
		$semanticDataOfWonderland = $this->semanticDataFactory
			->setTitle( 'BananaWonderland' )
			->newEmptySemanticData();

		$semanticDataOfWonderland->addDataValue(
			$this->newDataValueForPagePropertyValue( 'MemberOf', 'Wonderland' )
		);

		$this->getStore()->updateData( $semanticDataOfDreamland );
		$this->getStore()->updateData( $semanticDataOfWonderland );
		$this->getStore()->updateData( $semanticDataOfDangerland );

		$someProperty = new SomeProperty(
			DIProperty::newFromUserLabel( 'LocatedIn' )->setPropertyTypeId( '_wpg' ),
			new SomeProperty(
				DIProperty::newFromUserLabel( 'MemberOf' )->setPropertyTypeId( '_wpg' ),
				new ValueDescription(
					new DIWikiPage( 'Wonderland', NS_MAIN, '' ),
					DIProperty::newFromUserLabel( 'MemberOf' )->setPropertyTypeId( '_wpg' ), SMW_CMP_EQ
				)
			)
		);

		$classDescription = new ClassDescription(
			new DIWikiPage( 'WickedPlaces', NS_CATEGORY, '' )
		);

		$description = new Disjunction();
		$description->addDescription( $classDescription );
		$description->addDescription( $someProperty );

		$this->assertEquals(
			$description,
			$this->queryParser->getQueryDescription( '[[Category:WickedPlaces]] OR [[LocatedIn.MemberOf::Wonderland]]' )
		);

		$this->assertEquals(
			$description,
			$this->queryParser->getQueryDescription( '[[Category:WickedPlaces]] OR [[LocatedIn::<q>[[MemberOf::Wonderland]]</q>]]' )
		);

		$query = new Query(
			$description,
			false,
			false
		);

		$query->querymode = Query::MODE_INSTANCES;

		$queryResult = $this->getStore()->getQueryResult( $query );

		$expectedSubjects = array(
			$semanticDataOfDreamland->getSubject(),
			$semanticDataOfDangerland->getSubject()
		);

		$this->assertEquals(
			2,
			$queryResult->getCount()
		);

		$this->queryResultValidator->assertThatQueryResultHasSubjects(
			$expectedSubjects,
			$queryResult
		);

		$this->subjectsToBeCleared = array(
			$semanticDataOfWonderland->getSubject(),
			$semanticDataOfDreamland->getSubject(),
			$semanticDataOfDangerland->getSubject()
		);
	}

	/**
	 * {{#ask: [[LocatedIn.Has subobject.MemberOf::Wonderland]] }}
	 */
	public function testSubqueryForCombinedSubobjectPropertyChainThatComparesEqualToValue() {

		if ( !$this->getStore() instanceOf \SMWSQLStore3 ) {
			$this->markTestSkipped( "Property chain sub-queries with subobjects are currently only supported by the SQLStore" );
		}

		/**
		 * Page ...-dreamland annotated with [[LocatedIn::BananaWonderland]]
		 */
		$semanticDataOfDreamland = $this->semanticDataFactory
			->setTitle( __METHOD__ . '-dreamland' )
			->newEmptySemanticData();

		$semanticDataOfDreamland->addDataValue(
			$this->newDataValueForPagePropertyValue( 'LocatedIn', 'BananaWonderland' )
		);

		/**
		 * Page BananaWonderland annotated with [[Has subobject.MemberOf::Wonderland]]
		 */
		$semanticDataOfWonderland = $this->semanticDataFactory
			->setTitle( 'BananaWonderland' )
			->newEmptySemanticData();

		$subobject = new Subobject( $semanticDataOfWonderland->getSubject()->getTitle() );
		$subobject->setEmptySemanticDataForId( 'SomeSubobjectOnWonderland' );

		$subobject->addDataValue(
			$this->newDataValueForPagePropertyValue( 'MemberOf', 'Wonderland' )
		);

		$semanticDataOfWonderland->addPropertyObjectValue(
			$subobject->getProperty(),
			$subobject->getContainer()
		);

		$this->getStore()->updateData( $semanticDataOfDreamland );
		$this->getStore()->updateData( $semanticDataOfWonderland );

		$description = new SomeProperty(
			DIProperty::newFromUserLabel( 'LocatedIn' )->setPropertyTypeId( '_wpg' ),
			new SomeProperty(
				DIProperty::newFromUserLabel( '_SOBJ' )->setPropertyTypeId( '__sob' ),
				new SomeProperty(
					DIProperty::newFromUserLabel( 'MemberOf' )->setPropertyTypeId( '_wpg' ),
					new ValueDescription(
						new DIWikiPage( 'Wonderland', NS_MAIN, '' ),
						DIProperty::newFromUserLabel( 'MemberOf' )->setPropertyTypeId( '_wpg' ), SMW_CMP_EQ
					)
				)
			)
		);

		$this->assertEquals(
			$description,
			$this->queryParser->getQueryDescription( '[[LocatedIn.Has subobject.MemberOf::Wonderland]]' )
		);

		$query = new Query(
			$description,
			false,
			false
		);

		$query->querymode = Query::MODE_INSTANCES;

		$queryResult = $this->getStore()->getQueryResult( $query );

		$expectedSubjects = array(
			$semanticDataOfDreamland->getSubject()
		);

		$this->assertEquals(
			1,
			$queryResult->getCount()
		);

		$this->queryResultValidator->assertThatQueryResultHasSubjects(
			$expectedSubjects,
			$queryResult
		);

		$this->subjectsToBeCleared = array(
			$semanticDataOfWonderland->getSubject(),
			$semanticDataOfDreamland->getSubject()
		);
	}

	/**
	 * {{#ask: [[LocatedIn.Has subobject.MemberOf::+]] }}
	 */
	public function testSubqueryForCombinedSubobjectPropertyChainForWilcardSearch() {

		if ( !$this->getStore() instanceOf \SMWSQLStore3 ) {
			$this->markTestSkipped( "Property chain sub-queries with subobjects are currently only supported by the SQLStore" );
		}

		/**
		 * Page ...-dreamland annotated with [[LocatedIn::BananaWonderland]]
		 */
		$semanticDataOfDreamland = $this->semanticDataFactory
			->setTitle( __METHOD__ . '-dreamland' )
			->newEmptySemanticData();

		$semanticDataOfDreamland->addDataValue(
			$this->newDataValueForPagePropertyValue( 'LocatedIn', 'BananaWonderland' )
		);

		/**
		 * Page ...-fairyland annotated with [[LocatedIn::BananaWonderland]]
		 */
		$semanticDataOfFairyland = $this->semanticDataFactory
			->setTitle( __METHOD__ . '-fairyland' )
			->newEmptySemanticData();

		$semanticDataOfFairyland->addDataValue(
			$this->newDataValueForPagePropertyValue( 'LocatedIn', 'BananaWonderland' )
		);

		/**
		 * Page BananaWonderland annotated with [[Has subobject.MemberOf::Wonderland]]
		 */
		$semanticDataOfWonderland = $this->semanticDataFactory
			->setTitle( 'BananaWonderland' )
			->newEmptySemanticData();

		$subobject = new Subobject( $semanticDataOfWonderland->getSubject()->getTitle() );
		$subobject->setEmptySemanticDataForId( 'SomeSubobjectOnWonderland' );

		$subobject->addDataValue(
			$this->newDataValueForPagePropertyValue( 'MemberOf', 'Wonderland' )
		);

		$semanticDataOfWonderland->addPropertyObjectValue(
			$subobject->getProperty(),
			$subobject->getContainer()
		);

		$this->getStore()->updateData( $semanticDataOfDreamland );
		$this->getStore()->updateData( $semanticDataOfFairyland );
		$this->getStore()->updateData( $semanticDataOfWonderland );

		$description = new SomeProperty(
			DIProperty::newFromUserLabel( 'LocatedIn' )->setPropertyTypeId( '_wpg' ),
			new SomeProperty(
				DIProperty::newFromUserLabel( '_SOBJ' )->setPropertyTypeId( '__sob' ),
				new SomeProperty(
					DIProperty::newFromUserLabel( 'MemberOf' )->setPropertyTypeId( '_wpg' ),
					new ThingDescription()
				)
			)
		);

		$this->assertEquals(
			$description,
			$this->queryParser->getQueryDescription( '[[LocatedIn.Has subobject.MemberOf::+]]' )
		);

		$query = new Query(
			$description,
			false,
			false
		);

		$query->querymode = Query::MODE_INSTANCES;

		$queryResult = $this->getStore()->getQueryResult( $query );

		$expectedSubjects = array(
			$semanticDataOfDreamland->getSubject(),
			$semanticDataOfFairyland->getSubject()
		);

		$this->assertEquals(
			2,
			$queryResult->getCount()
		);

		$this->queryResultValidator->assertThatQueryResultHasSubjects(
			$expectedSubjects,
			$queryResult
		);

		$this->subjectsToBeCleared = array(
			$semanticDataOfWonderland->getSubject(),
			$semanticDataOfDreamland->getSubject(),
			$semanticDataOfFairyland->getSubject()
		);
	}

}
