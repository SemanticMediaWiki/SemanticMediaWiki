<?php

namespace SMW\Tests\Integration\Query;

use SMW\Tests\MwDBaseUnitTestCase;
use SMW\Tests\Util\SemanticDataFactory;
use SMW\Tests\Util\QueryResultValidator;

use SMW\Tests\Util\Fixtures\Properties\AreaProperty;
use SMW\Tests\Util\Fixtures\Facts\Berlin;

use SMW\Query\Language\ThingDescription;
use SMW\Query\Language\SomeProperty;

use SMW\DIWikiPage;
use SMW\DIProperty;
use SMW\DataValueFactory;

use SMWQuery as Query;
use SMWPrintRequest as PrintRequest;
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

		$areaProperty = new AreaProperty();
		$this->getStore()->updateData( $areaProperty->getSemanticDataForConversionValues() );
	}

	protected function tearDown() {

		foreach ( $this->subjectsToBeCleared as $subject ) {
			$this->getStore()->deleteSubject( $subject->getTitle() );
		}

		parent::tearDown();
	}

	public function testUserDefinedQuantityProperty() {

		$berlin = new Berlin();

		$areaDataValue = $berlin->getAreaDataValue();
		$property = $areaDataValue->getProperty();

		$semanticData = $this->semanticDataFactory->newEmptySemanticData( __METHOD__ );
		$semanticData->addDataValue( $areaDataValue );

		$this->getStore()->updateData( $semanticData );

		$this->assertArrayHasKey(
			$property->getKey(),
			$this->getStore()->getSemanticData( $semanticData->getSubject() )->getProperties()
		);

		$propertyValue = new PropertyValue( '__pro' );
		$propertyValue->setDataItem( $property );

		$description = new SomeProperty(
			$property,
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
			$areaDataValue,
			$queryResult
		);

		$this->subjectsToBeCleared = array(
			$semanticData->getSubject(),
			$property->getDiWikiPage()
		);
	}

	public function testUserDefinedTemperatureProperty() {

		$berlin = new Berlin();

		$temperatureDataValue = $berlin->getAverageHighTemperatureDataValue();
		$property = $temperatureDataValue->getProperty();

		$semanticData = $this->semanticDataFactory->newEmptySemanticData( __METHOD__ );
		$semanticData->addDataValue( $temperatureDataValue );

		$this->getStore()->updateData( $semanticData );

		$this->assertArrayHasKey(
			$property->getKey(),
			$this->getStore()->getSemanticData( $semanticData->getSubject() )->getProperties()
		);

		$propertyValue = new PropertyValue( '__pro' );
		$propertyValue->setDataItem( $property );

		$description = new SomeProperty(
			$property,
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
			$temperatureDataValue,
			$queryResult
		);

		$this->subjectsToBeCleared = array(
			$semanticData->getSubject(),
			$property->getDiWikiPage()
		);
	}

}
