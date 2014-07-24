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
