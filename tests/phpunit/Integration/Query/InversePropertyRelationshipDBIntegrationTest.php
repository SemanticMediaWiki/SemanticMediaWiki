<?php

namespace SMW\Tests\Integration\Query;

use SMW\ApplicationFactory;
use SMW\DataValueFactory;
use SMW\DIProperty;
use SMW\DIWikiPage;
use SMW\Query\Language\SomeProperty;
use SMW\Query\Language\ValueDescription;
use SMW\Tests\MwDBaseUnitTestCase;
use SMW\Tests\Utils\UtilityFactory;
use SMWQuery as Query;

/**
 * @see http://semantic-mediawiki.org/wiki/Inverse_Properties
 *
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
class InversePropertyRelationshipDBIntegrationTest extends MwDBaseUnitTestCase {

	private $subjectsToBeCleared = [];

	private $semanticDataFactory;
	private $dataValueFactory;

	private $queryResultValidator;
	private $queryParser;

	protected function setUp() {
		parent::setUp();

		$this->queryResultValidator = UtilityFactory::getInstance()->newValidatorFactory()->newQueryResultValidator();
		$this->semanticDataFactory = UtilityFactory::getInstance()->newSemanticDataFactory();

		$this->dataValueFactory = DataValueFactory::getInstance();
		$this->queryParser = ApplicationFactory::getInstance()->getQueryFactory()->newQueryParser();
	}

	protected function tearDown() {

		foreach ( $this->subjectsToBeCleared as $subject ) {
			$this->getStore()->deleteSubject( $subject->getTitle() );
		}

		parent::tearDown();
	}

	/**
	 * {{#ask: [[-Has mother::Michael]] }}
	 */
	public function testParentChildInverseRelationshipQuery() {

		$semanticData = $this->semanticDataFactory
			->setTitle( 'Michael' )
			->newEmptySemanticData();

		$semanticData->addDataValue(
			$this->newDataValueForPagePropertyValue( 'Has mother', 'Carol' )
		);

		$this->getStore()->updateData( $semanticData );

		$description = new SomeProperty(
			DIProperty::newFromUserLabel( 'Has mother', true )->setPropertyTypeId( '_wpg' ),
			new ValueDescription(
				new DIWikiPage( 'Michael', NS_MAIN, '' ),
				DIProperty::newFromUserLabel( 'Has mother', true )->setPropertyTypeId( '_wpg' ),
				SMW_CMP_EQ
			)
		);

		$this->assertEquals(
			$description,
			$this->queryParser->getQueryDescription( '[[-Has mother::Michael]]' )
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

		$expectedSubjects = [
			new DIWikiPage( 'Carol', NS_MAIN, '' )
		];

		$this->queryResultValidator->assertThatQueryResultHasSubjects(
			$expectedSubjects,
			$queryResult
		);

		$this->subjectsToBeCleared = [
			$semanticData->getSubject()
		];
	}

	private function newDataValueForPagePropertyValue( $property, $value ) {

		$property = DIProperty::newFromUserLabel( $property );
		$property->setPropertyTypeId( '_wpg' );

		$dataItem = new DIWikiPage( $value, NS_MAIN, '' );

		return $this->dataValueFactory->newDataValueByItem(
			$dataItem,
			$property
		);
	}

}
