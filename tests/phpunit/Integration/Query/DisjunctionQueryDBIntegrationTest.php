<?php

namespace SMW\Tests\Integration\Query;

use SMW\Tests\MwDBaseUnitTestCase;
use SMW\Tests\Utils\UtilityFactory;

use SMW\Query\Language\ThingDescription;
use SMW\Query\Language\ValueDescription;
use SMW\Query\Language\Conjunction;
use SMW\Query\Language\Disjunction;
use SMW\Query\Language\ClassDescription;
use SMW\Query\Language\SomeProperty;

use SMW\DIWikiPage;
use SMW\DIProperty;
use SMW\SemanticData;

use SMWQueryParser as QueryParser;
use SMWDIBlob as DIBlob;
use SMWDINumber as DINumber;
use SMWQuery as Query;
use SMWPropertyValue as PropertyValue;

/**
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
class DisjunctionQueryDBIntegrationTest extends MwDBaseUnitTestCase {

	private $subjectsToBeCleared = array();
	private $semanticDataFactory;
	private $queryResultValidator;
	private $queryParser;

	protected function setUp() {
		parent::setUp();

		$this->queryResultValidator = UtilityFactory::getInstance()->newValidatorFactory()->newQueryResultValidator();
		$this->semanticDataFactory = UtilityFactory::getInstance()->newSemanticDataFactory();
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
	 * {{#ask: [[Category:WickedPlaces]] OR [[LocatedIn.MemberOf::Wonderland]] }}
	 */
	public function testDisjunctionSubqueryForPageTypePropertyChainThatComparesEqualToValue() {

		/**
		 * Page ...-dangerland annotated with [[Category:WickedPlaces]]
		 */
		$semanticDataOfDangerland = $this->semanticDataFactory
			->setTitle( __METHOD__ . '-dangerland' )
			->newEmptySemanticData();

		$semanticDataOfDangerland->addPropertyObjectValue(
			new DIProperty( '_INST' ),
			new DIWikiPage( 'WickedPlaces', NS_CATEGORY )
		);

		$this->subjectsToBeCleared[] = $semanticDataOfDangerland->getSubject();
		$this->getStore()->updateData( $semanticDataOfDangerland );

		/**
		 * Page ...-dreamland annotated with [[LocatedIn::BananaWonderland]]
		 */
		$semanticDataOfDreamland = $this->semanticDataFactory
			->setTitle( __METHOD__ . '-dreamland' )
			->newEmptySemanticData();

		$semanticDataOfDreamland->addPropertyObjectValue(
			DIProperty::newFromUserLabel( 'LocatedIn' )->setPropertyTypeId( '_wpg' ),
			new DIWikiPage( 'BananaWonderland', NS_MAIN )
		);

		$this->subjectsToBeCleared[] = $semanticDataOfDreamland->getSubject();
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

		$this->subjectsToBeCleared[] = $semanticDataOfWonderland->getSubject();
		$this->getStore()->updateData( $semanticDataOfWonderland );

		/**
		 * Query with [[Category:WickedPlaces]] OR [[LocatedIn.MemberOf::Wonderland]]
		 */
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
	}

	public function testSubqueryDisjunction() {

		$property = new DIProperty( 'HasSomeProperty' );
		$property->setPropertyTypeId( '_wpg' );

		/**
		 * Page annotated with [[HasSomeProperty:Foo]]
		 */
		$semanticData = $this->semanticDataFactory->newEmptySemanticData( __METHOD__ . '1' );

		$semanticData->addPropertyObjectValue(
			$property,
			new DIWikiPage( 'Foo', NS_MAIN )
		);

		$expectedSubjects[] = $semanticData->getSubject();
		$this->subjectsToBeCleared[] = $semanticData->getSubject();

		$this->getStore()->updateData( $semanticData );

		/**
		 * Page annotated with [[HasSomeProperty:Bar]]
		 */
		$semanticData = $this->semanticDataFactory->newEmptySemanticData( __METHOD__ . '2' );

		$semanticData->addPropertyObjectValue(
			$property,
			new DIWikiPage( 'Bar', NS_MAIN )
		);

		$expectedSubjects[] = $semanticData->getSubject();
		$this->subjectsToBeCleared[] = $semanticData->getSubject();

		$this->getStore()->updateData( $semanticData );

		/**
		 * Query with [[HasSomeProperty::Foo||Bar]]
		 */
		$disjunction = new Disjunction( array(
			new ValueDescription( new DIWikiPage( 'Foo', NS_MAIN ), $property ),
			new ValueDescription( new DIWikiPage( 'Bar', NS_MAIN ), $property )
		) );

		$description = new SomeProperty(
			$property,
			$disjunction
		);

		$query = new Query(
			$description,
			false,
			false
		);

		$this->assertEquals(
			$description,
			$this->queryParser->getQueryDescription( '[[HasSomeProperty::Foo||Bar]]' )
		);

		$query->querymode = Query::MODE_INSTANCES;

		$queryResult = $this->getStore()->getQueryResult( $query );

		$this->assertEquals(
			2,
			$queryResult->getCount()
		);

		$this->queryResultValidator->assertThatQueryResultHasSubjects(
			$expectedSubjects,
			$queryResult
		);
	}

}
