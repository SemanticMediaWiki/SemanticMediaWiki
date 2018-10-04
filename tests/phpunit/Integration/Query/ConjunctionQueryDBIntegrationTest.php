<?php

namespace SMW\Tests\Integration\Query;

use SMW\ApplicationFactory;
use SMW\DIProperty;
use SMW\DIWikiPage;
use SMW\Query\Language\ClassDescription;
use SMW\Query\Language\Conjunction;
use SMW\Query\Language\SomeProperty;
use SMW\Query\Language\ValueDescription;
use SMW\Tests\MwDBaseUnitTestCase;
use SMW\Tests\Utils\UtilityFactory;
use SMWQuery as Query;

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
class ConjunctionQueryDBIntegrationTest extends MwDBaseUnitTestCase {

	private $subjectsToBeCleared = [];
	private $semanticDataFactory;
	private $queryResultValidator;
	private $queryParser;

	protected function setUp() {
		parent::setUp();

		$utilityFactory = UtilityFactory::getInstance();

		$this->semanticDataFactory  = $utilityFactory->newSemanticDataFactory();
		$this->queryResultValidator = $utilityFactory->newValidatorFactory()->newQueryResultValidator();

		$this->fixturesProvider = $utilityFactory->newFixturesFactory()->newFixturesProvider();
		$this->fixturesProvider->setupDependencies( $this->getStore() );

		$this->queryParser = ApplicationFactory::getInstance()->getQueryFactory()->newQueryParser();
	}

	protected function tearDown() {

		$fixturesCleaner = UtilityFactory::getInstance()->newFixturesFactory()->newFixturesCleaner();
		$fixturesCleaner
			->purgeSubjects( $this->subjectsToBeCleared )
			->purgeAllKnownFacts();

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

		$expectedSubjects = [
			$semanticDataOfDreamland->getSubject()
		];

		$this->assertEquals(
			1,
			$queryResult->getCount()
		);

		$this->queryResultValidator->assertThatQueryResultHasSubjects(
			$expectedSubjects,
			$queryResult
		);

		$this->subjectsToBeCleared = [
			$semanticDataOfWonderland->getSubject(),
			$semanticDataOfDreamland->getSubject(),
			$semanticDataOfNeverland->getSubject()
		];
	}

	public function testNestedPropertyConjunction() {

		/**
		 * Page annotated with [[Born in::Paris]]
		 */
		$property = DIProperty::newFromUserLabel( 'Born in' );
		$property->setPropertyTypeId( '_wpg' );

		$semanticData = $this->semanticDataFactory->newEmptySemanticData( __METHOD__ . 'PageOughtToBeSelected' );

		$semanticData->addPropertyObjectValue(
			$property,
			new DIWikiPage( 'Paris', NS_MAIN )
		);

		$expectedSubjects = $semanticData->getSubject();
		$this->subjectsToBeCleared[] = $semanticData->getSubject();

		$this->getStore()->updateData( $semanticData );

		$this->getStore()->updateData(
			$this->fixturesProvider->getFactsheet( 'Paris' )->asEntity()
		);

		/**
		 * @query [[Born in::<q>[[Category:City]] [[Located in::France]]</q>]]
		 */
		$cityCategory = $this->fixturesProvider->getCategory( 'city' )->asSubject();
		$locatedInProperty = $this->fixturesProvider->getProperty( 'locatedin' );

		$conjunction = new Conjunction( [
			new ClassDescription( $cityCategory ),
			new SomeProperty(
				$locatedInProperty,
				new ValueDescription(
					$this->fixturesProvider->getFactsheet( 'France' )->asSubject(),
					$locatedInProperty )
				)
			]
		);

		$description = new SomeProperty(
			$property,
			$conjunction
		);

		$this->assertEquals(
			$description,
			$this->queryParser->getQueryDescription( '[[Born in::<q>[[Category:City]] [[Located in::France]]</q>]]' )
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
