<?php

namespace SMW\Tests\Integration\Query;

use SMW\Tests\MwDBaseUnitTestCase;
use SMW\Tests\Utils\UtilityFactory;

use SMW\Query\Language\ThingDescription;
use SMW\Query\Language\SomeProperty;
use SMW\Query\PrintRequestFactory;

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
class CustomUnitDataTypeQueryDBIntegrationTest extends MwDBaseUnitTestCase {

	private $fixturesProvider;
	private $semanticDataFactory;
	private $queryResultValidator;

	private $dataValueFactory;
	private $printRequestFactory;

	private $subjects = array();

	protected function setUp() {
		parent::setUp();

		$this->dataValueFactory = DataValueFactory::getInstance();
		$this->printRequestFactory = new PrintRequestFactory();

		$this->semanticDataFactory  = UtilityFactory::getInstance()->newSemanticDataFactory();
		$this->queryResultValidator = UtilityFactory::getInstance()->newValidatorFactory()->newQueryResultValidator();

		$this->fixturesProvider = UtilityFactory::getInstance()->newFixturesFactory()->newFixturesProvider();
		$this->fixturesProvider->setupDependencies( $this->getStore() );
	}

	protected function tearDown() {

		$fixturesCleaner = UtilityFactory::getInstance()->newFixturesFactory()->newFixturesCleaner();
		$fixturesCleaner
			->purgeSubjects( $this->subjects )
			->purgeAllKnownFacts();

		parent::tearDown();
	}

	public function testUserDefinedQuantityProperty() {

		$semanticData = $this->semanticDataFactory->newEmptySemanticData( __METHOD__ );
		$this->subjects[] = $semanticData->getSubject();

		$factsheet = $this->fixturesProvider->getFactsheet( 'Berlin' );
		$factsheet->setTargetSubject( $semanticData->getSubject() );

		$areaValue = $factsheet->getAreaValue();
		$areaProperty = $areaValue->getProperty();

		$semanticData->addDataValue( $areaValue );
		$this->getStore()->updateData( $semanticData );

		$this->assertArrayHasKey(
			$areaProperty->getKey(),
			$this->getStore()->getSemanticData( $semanticData->getSubject() )->getProperties()
		);

		/**
		 * @query [[Area::+]]|?Area
		 */
		$description = new SomeProperty(
			$areaProperty,
			new ThingDescription()
		);

		$description->addPrintRequest(
			$this->printRequestFactory->newPropertyPrintRequest( $areaProperty )
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
	}

	public function testUserDefinedTemperatureProperty() {

		$semanticData = $this->semanticDataFactory->newEmptySemanticData( __METHOD__ );
		$this->subjects[] = $semanticData->getSubject();

		$factsheet = $this->fixturesProvider->getFactsheet( 'Berlin' );
		$factsheet->setTargetSubject( $semanticData->getSubject() );

		$temperatureValue = $factsheet->getAverageHighTemperatureValue();
		$temperatureProperty = $temperatureValue->getProperty();

		$semanticData->addDataValue( $temperatureValue );
		$this->getStore()->updateData( $semanticData );

		$this->assertArrayHasKey(
			$temperatureProperty->getKey(),
			$this->getStore()->getSemanticData( $semanticData->getSubject() )->getProperties()
		);

		/**
		 * @query [[Temperature::+]]|?Temperature
		 */
		$description = new SomeProperty(
			$temperatureProperty,
			new ThingDescription()
		);

		$description->addPrintRequest(
			$this->printRequestFactory->newPropertyPrintRequest( $temperatureProperty )
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
	}

}
