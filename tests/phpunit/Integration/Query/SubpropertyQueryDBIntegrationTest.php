<?php

namespace SMW\Tests\Integration\Query;

use SMW\Tests\MwDBaseUnitTestCase;
use SMW\Tests\Util\SemanticDataFactory;
use SMW\Tests\Util\QueryResultValidator;

use SMW\DIWikiPage;
use SMW\DIProperty;
use SMW\DataValueFactory;

use SMWQuery as Query;
use SMWSomeProperty as SomeProperty;
use SMWPrintRequest as PrintRequest;
use SMWPropertyValue as PropertyValue;
use SMWThingDescription as ThingDescription;

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
class SubpropertyQueryDBIntegrationTest extends MwDBaseUnitTestCase {

	protected $databaseToBeExcluded = array( 'sqlite' );

	private $subjectsToBeCleared = array();
	private $semanticDataFactory;
	private $dataValueFactory;
	private $queryResultValidator;

	protected function setUp() {
		parent::setUp();

		$this->dataValueFactory = DataValueFactory::getInstance();
		$this->semanticDataFactory = new SemanticDataFactory();
		$this->queryResultValidator = new QueryResultValidator();
	}

	protected function tearDown() {

		foreach ( $this->subjectsToBeCleared as $subject ) {
			$this->getStore()->deleteSubject( $subject->getTitle() );
		}

		parent::tearDown();
	}

	public function testSubpropertyToQueryFromTopHierarchy() {

		if ( $this->getDBConnection()->getType() == 'postgres' ) {
			$this->markTestSkipped( "Issue with postgres, for details see #462" );
		}

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
