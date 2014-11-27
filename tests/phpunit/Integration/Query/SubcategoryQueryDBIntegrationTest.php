<?php

namespace SMW\Tests\Integration\Query;

use SMW\Tests\MwDBaseUnitTestCase;
use SMW\Tests\Utils\UtilityFactory;

use SMW\Query\Language\ClassDescription;

use SMW\DIWikiPage;
use SMW\DIProperty;
use SMW\DataValueFactory;

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
class SubcategoryQueryDBIntegrationTest extends MwDBaseUnitTestCase {

	private $subjectsToBeCleared = array();
	private $semanticDataFactory;

	private $dataValueFactory;
	private $queryResultValidator;

	protected function setUp() {
		parent::setUp();

		$this->dataValueFactory = DataValueFactory::getInstance();
		$this->semanticDataFactory = UtilityFactory::getInstance()->newSemanticDataFactory();
		$this->queryResultValidator = UtilityFactory::getInstance()->newValidatorFactory()->newQueryResultValidator();
	}

	protected function tearDown() {

		foreach ( $this->subjectsToBeCleared as $subject ) {
			$this->getStore()->deleteSubject( $subject->getTitle() );
		}

		parent::tearDown();
	}

	public function testSubcategoryHierarchyToQueryAllMembersOfTopCategory() {

		if ( !$this->getStore() instanceOf \SMWSQLStore3 ) {
			$this->markTestSkipped( "Subcategory/category hierarchies are currently only supported by the SQLStore" );
		}

		$property = new DIProperty( '_INST' );

		$semanticDataOfTopCategory = $this->semanticDataFactory
			->setSubject( new DIWikiPage( 'TopCategory', NS_CATEGORY, '' ) )
			->newEmptySemanticData();

		$semanticDataOfOtherCategory = $this->semanticDataFactory
			->setSubject( new DIWikiPage( 'OtherCategory', NS_CATEGORY, '' ) )
			->newEmptySemanticData();

		$dataValueOfSubproperty = $this->dataValueFactory->newDataItemValue(
			$semanticDataOfTopCategory->getSubject(),
			new DIProperty( '_SUBC' )
		);

		$semanticDataOfOtherCategory->addDataValue( $dataValueOfSubproperty	);

		$dataValue = $this->dataValueFactory->newDataItemValue(
			$semanticDataOfOtherCategory->getSubject(),
			$property
		);

		$semanticData = $this->semanticDataFactory->newEmptySemanticData( __METHOD__ );
		$semanticData->addDataValue( $dataValue	);

		$this->getStore()->updateData( $semanticDataOfTopCategory );
		$this->getStore()->updateData( $semanticDataOfOtherCategory );
		$this->getStore()->updateData( $semanticData );

		$description = new ClassDescription(
			new DIWikiPage( 'TopCategory', NS_CATEGORY, '' )
		);

		$query = new Query(
			$description,
			false,
			false
		);

		$query->querymode = Query::MODE_INSTANCES;

		$this->queryResultValidator->assertThatQueryResultHasSubjects(
			$semanticData->getSubject(),
			$this->getStore()->getQueryResult( $query )
		);

		$this->subjectsToBeCleared = array(
			$semanticData->getSubject(),
			$semanticDataOfOtherCategory->getSubject(),
			$semanticDataOfTopCategory->getSubject(),
		);
	}

}
