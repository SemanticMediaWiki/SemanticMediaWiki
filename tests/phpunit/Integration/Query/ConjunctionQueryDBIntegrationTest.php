<?php

namespace SMW\Tests\Integration\Query;

use SMW\Tests\MwDBaseUnitTestCase;
use SMW\Tests\Util\SemanticDataFactory;
use SMW\Tests\Util\QueryResultValidator;

use SMW\DIWikiPage;
use SMW\DIProperty;
use SMW\SemanticData;

use SMWQueryParser as QueryParser;
use SMWDIBlob as DIBlob;
use SMWDINumber as DINumber;
use SMWQuery as Query;
use SMWSomeProperty as SomeProperty;
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
class ConjunctionQueryDBIntegrationTest extends MwDBaseUnitTestCase {

	protected $databaseToBeExcluded = array( 'sqlite' );

	private $subjectsToBeCleared = array();
	private $semanticDataFactory;
	private $queryResultValidator;
	private $queryParser;

	protected function setUp() {
		parent::setUp();

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
	 * {{#ask: [[Category:HappyPlaces]] [[LocatedIn.MemberOf::Wonderland]] }}
	 */
	public function testConjunctionForCategoryAndPropertyChainSubqueryThatComparesEqualToSpecifiedValue() {

		/**
		 * Page ...-neverland annotated with [[LocatedIn::BananaWonderland]]
		 */
		$semanticDataOfNeverland = $this->semanticDataFactory
			->setTitle( __METHOD__ . '-neverland' )
			->newEmptySemanticData();

		$semanticDataOfNeverland->addPropertyObjectValue(
			DIProperty::newFromUserLabel( 'LocatedIn' )->setPropertyTypeId( '_wpg' ),
			new DIWikiPage( 'BananaWonderland', NS_MAIN )
		);

		$this->getStore()->updateData( $semanticDataOfNeverland );

		/**
		 * Page ...-dreamland annotated with [[Category:HappyPlaces]] [[LocatedIn::BananaWonderland]]
		 */
		$semanticDataOfDreamland = $this->semanticDataFactory
			->setTitle( __METHOD__ . '-dreamland' )
			->newEmptySemanticData();

		$semanticDataOfDreamland->addPropertyObjectValue(
			DIProperty::newFromUserLabel( 'LocatedIn' )->setPropertyTypeId( '_wpg' ),
			new DIWikiPage( 'BananaWonderland', NS_MAIN )
		);

		$semanticDataOfDreamland->addPropertyObjectValue(
			new DIProperty( '_INST' ),
			new DIWikiPage( 'HappyPlaces', NS_CATEGORY )
		);

		$this->getStore()->updateData( $semanticDataOfDreamland );

		/**
		 * Page BananaWonderland annotated with [[MemberOf::Wonderland]]
		 */
		$semanticDataOfWonderland = $this->semanticDataFactory
			->setTitle( 'BananaWonderland' )
			->newEmptySemanticData();

		$semanticDataOfWonderland->addPropertyObjectValue(
			DIProperty::newFromUserLabel( 'MemberOf' )->setPropertyTypeId( '_wpg' ),
			new DIWikiPage( 'Wonderland', NS_MAIN )
		);

		$this->getStore()->updateData( $semanticDataOfWonderland );

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

	public function testNestedPropertyConjunction() {

		$property = DIProperty::newFromUserLabel( 'Born in' );
		$property->setPropertyTypeId( '_wpg' );

		/**
		 * Page annotated with [[Born in::Nomansland]]
		 */
		$semanticData = $this->semanticDataFactory->newEmptySemanticData( __METHOD__ . 'PageOughtToBeSelected' );

		$semanticData->addPropertyObjectValue(
			$property,
			new DIWikiPage( 'Nomansland', NS_MAIN )
		);

		$expectedSubjects = $semanticData->getSubject();
		$this->subjectsToBeCleared[] = $semanticData->getSubject();

		$this->getStore()->updateData( $semanticData );

		/**
		 * Page annotated with [[Category:City]] [[Located in::Outback]]
		 */
		$semanticData = $this->semanticDataFactory->newEmptySemanticData( 'Nomansland' );

		$semanticData->addPropertyObjectValue(
			DIProperty::newFromUserLabel( 'Located in' )->setPropertyTypeId( '_wpg' ),
			new DIWikiPage( 'Outback', NS_MAIN )
		);

		$semanticData->addPropertyObjectValue(
			new DIProperty( '_INST' ),
			new DIWikiPage( 'City', NS_CATEGORY )
		);

		$this->subjectsToBeCleared[] = $semanticData->getSubject();
		$this->getStore()->updateData( $semanticData );

		/**
		 * Query with [[Born in::<q>[[Category:City]] [[Located in::Outback]]</q>]]
		 */
		$conjunction = new Conjunction( array(
			new ClassDescription( new DIWikiPage( 'City', NS_CATEGORY ) ),
			new SomeProperty(
				DIProperty::newFromUserLabel( 'Located in' )->setPropertyTypeId( '_wpg' ),
				new ValueDescription(
					new DIWikiPage( 'Outback', NS_MAIN ),
					DIProperty::newFromUserLabel( 'Located in' )->setPropertyTypeId( '_wpg' ) )
				)
			)
		);

		$description = new SomeProperty(
			$property,
			$conjunction
		);

		$this->assertEquals(
			$description,
			$this->queryParser->getQueryDescription( '[[Born in::<q>[[Category:City]] [[Located in::Outback]]</q>]]' )
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

		$this->queryResultValidator->assertThatQueryResultHasSubjects(
			$expectedSubjects,
			$queryResult
		);
	}

}
