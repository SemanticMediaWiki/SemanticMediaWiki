<?php

namespace SMW\Tests\Integration\Query;

use SMW\Tests\MwDBaseUnitTestCase;
use SMW\Tests\Util\SemanticDataFactory;
use SMW\Tests\Util\Validators\QueryResultValidator;

use SMW\Tests\Util\Fixtures\FixturesBuilder;
use SMW\Tests\Util\Fixtures\Facts\BerlinFact;

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
class CustomUnitDataTypeQueryDBIntegrationTest extends MwDBaseUnitTestCase {

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

		$fixturesBuilder = new FixturesBuilder();
		$fixturesBuilder->updateFixtureDependencies( $this->getStore() );
	}

	protected function tearDown() {

		foreach ( $this->subjectsToBeCleared as $subject ) {
			$this->getStore()->deleteSubject( $subject->getTitle() );
		}

		parent::tearDown();
	}

	public function testUserDefinedQuantityProperty() {

		$berlinFact = new BerlinFact();

		$areaValue = $berlinFact->getAreaValue();
		$areaProperty = $areaValue->getProperty();

		$semanticData = $this->semanticDataFactory->newEmptySemanticData( __METHOD__ );
		$semanticData->addDataValue( $areaValue );

		$this->getStore()->updateData( $semanticData );

		$this->assertArrayHasKey(
			$areaProperty->getKey(),
			$this->getStore()->getSemanticData( $semanticData->getSubject() )->getProperties()
		);

		$propertyValue = new PropertyValue( '__pro' );
		$propertyValue->setDataItem( $areaProperty );

		$description = new SomeProperty(
			$areaProperty,
			new ThingDescription()
		);

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
			$areaValue,
			$queryResult
		);

		$this->subjectsToBeCleared = array(
			$semanticData->getSubject(),
			$areaProperty->getDiWikiPage()
		);
	}

	public function testUserDefinedTemperatureProperty() {

		$berlinFact = new BerlinFact();

		$temperatureValue = $berlinFact->getAverageHighTemperatureValue();
		$temperatureProperty = $temperatureValue->getProperty();

		$semanticData = $this->semanticDataFactory->newEmptySemanticData( __METHOD__ );
		$semanticData->addDataValue( $temperatureValue );

		$this->getStore()->updateData( $semanticData );

		$this->assertArrayHasKey(
			$temperatureProperty->getKey(),
			$this->getStore()->getSemanticData( $semanticData->getSubject() )->getProperties()
		);

		$propertyValue = new PropertyValue( '__pro' );
		$propertyValue->setDataItem( $temperatureProperty );

		$description = new SomeProperty(
			$temperatureProperty,
			new ThingDescription()
		);

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
			$temperatureValue,
			$queryResult
		);

		$this->subjectsToBeCleared = array(
			$semanticData->getSubject(),
			$temperatureProperty->getDiWikiPage()
		);
	}

}
