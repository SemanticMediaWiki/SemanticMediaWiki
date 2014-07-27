<?php

namespace SMW\Tests\Integration\Query;

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
class SubqueryDBIntegrationTest extends MwDBaseUnitTestCase {

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
	 * {{#ask: [[LocatedIn.Has subobject.MemberOf::Wonderland]] }}
	 */
	public function testSubqueryForCombinedSubobjectPropertyChainThatComparesEqualToValue() {

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

	private function newDataValueForPagePropertyValue( $property, $value ) {

		$property = new DIProperty( $property );
		$property->setPropertyTypeId( '_wpg' );

		$dataItem = new DIWikiPage( $value, NS_MAIN, '' );

		return $this->dataValueFactory->newDataItemValue(
			$dataItem,
			$property
		);
	}

}
