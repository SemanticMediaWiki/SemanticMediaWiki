<?php

namespace SMW\Tests\Integration\Query;

use SMW\Tests\MwDBaseUnitTestCase;
use SMW\Tests\Utils\UtilityFactory;

use SMW\Query\Language\ThingDescription;
use SMW\Query\Language\ValueDescription;
use SMW\Query\Language\ClassDescription;
use SMW\Query\Language\SomeProperty;

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
use SMWPrintRequest as PrintRequest;
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
class SubqueryDBIntegrationTest extends MwDBaseUnitTestCase {

	private $subjectsToBeCleared = array();
	private $semanticDataFactory;
	private $dataValueFactory;

	private $queryResultValidator;
	private $queryParser;

	protected function setUp() {
		parent::setUp();

		$this->dataValueFactory = DataValueFactory::getInstance();
		$this->queryParser = new QueryParser();

		$this->queryResultValidator = UtilityFactory::getInstance()->newValidatorFactory()->newQueryResultValidator();
		$this->semanticDataFactory = UtilityFactory::getInstance()->newSemanticDataFactory();

		$this->fixturesProvider = UtilityFactory::getInstance()->newFixturesFactory()->newFixturesProvider();
		$this->fixturesProvider->setupDependencies( $this->getStore() );
	}

	protected function tearDown() {

		$fixturesCleaner = UtilityFactory::getInstance()->newFixturesFactory()->newFixturesCleaner();
		$fixturesCleaner
			->purgeSubjects( $this->subjectsToBeCleared )
			->purgeAllKnownFacts();

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
		$subobject->setEmptyContainerForId( 'SomeSubobjectOnWonderland' );

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
		$subobject->setEmptyContainerForId( 'SomeSubobjectOnWonderland' );

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

	public function testSingletonPropertyChain() {

		$semanticData = $this->semanticDataFactory->newEmptySemanticData( 'SingletonPropertyChain' );

		$semanticData->addDataValue(
			$this->newDataValueForPagePropertyValue( 'HasSomeProperty', 'Ichi' )
		);

		$semanticData->addDataValue(
			$this->newDataValueForPagePropertyValue( 'HasSomeProperty', 'Ni' )
		);

		$this->getStore()->updateData( $semanticData );

		$expectedSubjects = array(
			$semanticData->getSubject()
		);

		/**
		 * @query [[SingletonPropertyChain]] [[HasSomeProperty::Ichi||Ni]]
		 */
		$description = $this->queryParser
			->getQueryDescription( '[[SingletonPropertyChain]] [[HasSomeProperty::Ichi||Ni]]' );

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

		$this->queryResultValidator->assertThatQueryResultHasSubjects(
			$expectedSubjects,
			$queryResult
		);

		$this->subjectsToBeCleared = $expectedSubjects;
	}

	public function testConjunctiveSubobjectSubquery() {

		$paris = $this->fixturesProvider->getFactsheet( 'Paris' );
		$berlin = $this->fixturesProvider->getFactsheet( 'Berlin' );

		$this->getStore()->updateData( $paris->asEntity() );
		$this->getStore()->updateData( $berlin->asEntity() );

		/**
		 * @query [[Berlin]] [[Has subobject::<q>[[Area::891.85 km²]] [[Population::3517424]]</q>]]
		 */
		$description = $this->queryParser
			->getQueryDescription( '[[Berlin]] [[Has subobject::<q>[[Area::891.85 km²]] [[Population::3517424]]</q>]]' );

		$this->assertEmpty(
			$this->queryParser->getErrors()
		);

		$query = new Query(
			$description,
			false,
			false
		);

		$query->querymode = Query::MODE_INSTANCES;

		$expected = array(
			$berlin->asSubject()
		);

		$this->queryResultValidator->assertThatQueryResultHasSubjects(
			$expected,
			$this->getStore()->getQueryResult( $query )
		);
	}

	public function testDisjunctiveSubobjectSubquery() {

		$paris = $this->fixturesProvider->getFactsheet( 'Paris' );
		$berlin = $this->fixturesProvider->getFactsheet( 'Berlin' );

		$this->getStore()->updateData( $paris->asEntity() );
		$this->getStore()->updateData( $berlin->asEntity() );

		/**
		 * @query [[Has subobject::<q>[[Area::891.85 km²]] OR [[Population::2234105||3517424]]</q>]]
		 */
		$description = $this->queryParser
			->getQueryDescription( '[[Has subobject::<q>[[Area::891.85 km²||105.40 km²]] OR [[Population::2234105||3517424]]</q> ]]' );

		$this->assertEmpty(
			$this->queryParser->getErrors()
		);

		$query = new Query(
			$description,
			false,
			false
		);

		$query->querymode = Query::MODE_INSTANCES;

		$expected = array(
			$berlin->asSubject(),
			$paris->asSubject()
		);

		$this->queryResultValidator->assertThatQueryResultHasSubjects(
			$expected,
			$this->getStore()->getQueryResult( $query )
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
