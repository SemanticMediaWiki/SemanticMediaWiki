<?php

namespace SMW\Tests\Integration\Query;

use SMW\Query\Language\SomeProperty;
use SMW\Query\Language\ThingDescription;
use SMW\Query\PrintRequest;
use SMW\Tests\SMWIntegrationTestCase;
use SMW\Tests\Utils\UtilityFactory;
use SMWPropertyValue as PropertyValue;
use SMWQuery as Query;

/**
 * @group semantic-mediawiki-integration
 * @group semantic-mediawiki
 * @group Database
 * @group medium
 *
 * @license GPL-2.0-or-later
 * @since 2.2
 *
 * @author mwjames
 */
class RandomQueryResultOrderIntegrationTest extends SMWIntegrationTestCase {

	private $fixturesProvider;

	protected function setUp(): void {
		parent::setUp();

		$utilityFactory = UtilityFactory::getInstance();
		$utilityFactory->newMwHooksHandler()->invokeHooksFromRegistry();

		$this->fixturesProvider = $utilityFactory->newFixturesFactory()->newFixturesProvider();
		$this->fixturesProvider->setupDependencies( $this->getStore() );
	}

	protected function tearDown(): void {
		$fixturesCleaner = UtilityFactory::getInstance()->newFixturesFactory()->newFixturesCleaner();
		$fixturesCleaner->purgeAllKnownFacts();

		parent::tearDown();
	}

	public function testRandomOrder() {
		$factsheet = $this->fixturesProvider->getFactsheet( 'Berlin' );
		$populationValue = $factsheet->getPopulationValue();

		$this->getStore()->updateData( $factsheet->asEntity() );

		/**
		 * @query [[Population::+]]
		 */
		$property = $this->fixturesProvider->getProperty( 'Population' );

		$description = new SomeProperty(
			$property,
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
		$query->sort = true;
		$query->sortkeys = [ 'Population' => 'RANDOM' ];

		$queryResult = $this->getStore()->getQueryResult( $query );

		$this->assertEquals(
			3,
			$queryResult->getCount()
		);
	}

}
