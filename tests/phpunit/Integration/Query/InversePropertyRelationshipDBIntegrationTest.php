<?php

namespace SMW\Tests\Integration\Query;

use SMW\DataItems\Property;
use SMW\DataItems\WikiPage;
use SMW\DataValueFactory;
use SMW\Query\Language\SomeProperty;
use SMW\Query\Language\ValueDescription;
use SMW\Query\Query;
use SMW\Services\ServicesFactory as ApplicationFactory;
use SMW\Tests\SMWIntegrationTestCase;
use SMW\Tests\Utils\UtilityFactory;

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
 * @group Database
 * @group medium
 *
 * @license GPL-2.0-or-later
 * @since 2.0
 *
 * @author mwjames
 */
class InversePropertyRelationshipDBIntegrationTest extends SMWIntegrationTestCase {

	private $subjectsToBeCleared = [];

	private $semanticDataFactory;
	private $dataValueFactory;

	private $queryResultValidator;
	private $queryParser;

	protected function setUp(): void {
		parent::setUp();

		$this->queryResultValidator = UtilityFactory::getInstance()->newValidatorFactory()->newQueryResultValidator();
		$this->semanticDataFactory = UtilityFactory::getInstance()->newSemanticDataFactory();

		$this->dataValueFactory = DataValueFactory::getInstance();
		$this->queryParser = ApplicationFactory::getInstance()->getQueryFactory()->newQueryParser();
	}

	protected function tearDown(): void {
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
			Property::newFromUserLabel( 'Has mother', true )->setPropertyTypeId( '_wpg' ),
			new ValueDescription(
				new WikiPage( 'Michael', NS_MAIN, '' ),
				Property::newFromUserLabel( 'Has mother', true )->setPropertyTypeId( '_wpg' ),
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

		$this->assertSame(
			1,
			$queryResult->getCount()
		);

		$expectedSubjects = [
			new WikiPage( 'Carol', NS_MAIN, '' )
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
		$property = Property::newFromUserLabel( $property );
		$property->setPropertyTypeId( '_wpg' );

		$dataItem = new WikiPage( $value, NS_MAIN, '' );

		return $this->dataValueFactory->newDataValueByItem(
			$dataItem,
			$property
		);
	}

}
