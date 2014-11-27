<?php

namespace SMW\Tests\Integration\Query;

use SMW\Tests\MwDBaseUnitTestCase;
use SMW\Tests\Utils\UtilityFactory;

use SMW\Query\Language\ThingDescription;
use SMW\Query\Language\SomeProperty;

use SMW\DIWikiPage;
use SMW\DIProperty;
use SMW\DataValueFactory;

use SMWQuery as Query;
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
class SubpropertyQueryDBIntegrationTest extends MwDBaseUnitTestCase {

	private $subjectsToBeCleared = array();
	private $semanticDataFactory;

	private $dataValueFactory;
	private $queryResultValidator;

	protected function setUp() {
		parent::setUp();

		$this->dataValueFactory = DataValueFactory::getInstance();
		$this->queryResultValidator = UtilityFactory::getInstance()->newValidatorFactory()->newQueryResultValidator();
		$this->semanticDataFactory = UtilityFactory::getInstance()->newSemanticDataFactory();
	}

	protected function tearDown() {

		foreach ( $this->subjectsToBeCleared as $subject ) {
			$this->getStore()->deleteSubject( $subject->getTitle() );
		}

		parent::tearDown();
	}

	public function testSubpropertyToQueryFromTopHierarchy() {

		if ( !$this->getStore() instanceOf \SMWSQLStore3 ) {
			$this->markTestSkipped( "Subproperty/property hierarchies are currently only supported by the SQLStore" );
		}

		$semanticDataOfSpouse = $this->semanticDataFactory
			->setSubject( new DIWikiPage( 'Spouse', SMW_NS_PROPERTY, '' ) )
			->newEmptySemanticData();

		$property = new DIProperty( 'Wife' );
		$property->setPropertyTypeId( '_wpg' );

		$this->addPropertyHierarchy( $property, 'Spouse' );

		$dataValue = $this->dataValueFactory->newPropertyObjectValue(
			$property,
			'Lien'
		);

		$semanticData = $this->semanticDataFactory->newEmptySemanticData( __METHOD__ );
		$semanticData->addDataValue( $dataValue	);

		$this->getStore()->updateData( $semanticDataOfSpouse );
		$this->getStore()->updateData( $semanticData );

		$description = new SomeProperty(
			new DIProperty( 'Spouse' ),
			new ThingDescription()
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

		$queryResult = $this->getStore()->getQueryResult( $query );

		$this->queryResultValidator->assertThatQueryResultContains(
			$dataValue,
			$queryResult
		);

		$this->subjectsToBeCleared = array(
			$semanticData->getSubject(),
			$semanticDataOfSpouse->getSubject(),
			$property->getDiWikiPage()
		);
	}

	private function addPropertyHierarchy( DIProperty $property, $targetHierarchy ) {

		$semanticData = $this->semanticDataFactory->setSubject( $property->getDiWikiPage() )->newEmptySemanticData();

		$semanticData->addDataValue(
				$this->dataValueFactory->newPropertyObjectValue( new DIProperty( '_SUBP' ), $targetHierarchy )
		);

		$this->getStore()->updateData( $semanticData );
	}

}
