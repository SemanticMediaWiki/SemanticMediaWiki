<?php

namespace SMW\Tests\Integration\SPARQLStore;

use SMW\SemanticData;
use SMW\DIWikiPage;
use SMW\DIProperty;
use SMW\StoreFactory;
use SMW\DataValueFactory;

use SMWValueDescription as ValueDescription;
use SMWSomeProperty as SomeProperty;
use SMWPrintRequest as PrintRequest;
use SMWPropertyValue as PropertyValue;
use SMWThingDescription as ThingDescription;

use SMWQuery as Query;

/**
 * @ingroup Test
 *
 * @group SMW
 * @group SMWExtension
 * @group semantic-mediawiki-integration
 * @group semantic-mediawiki-sparql
 * @group semantic-mediawiki-query
 *
 * @license GNU GPL v2+
 * @since 1.9.3
 *
 * @author mwjames
 */
class QueryResultLookupWithoutBaseStoreIntegrationTest extends \PHPUnit_Framework_TestCase {

	private $store = null;

	protected function setUp() {

		$this->store = StoreFactory::getStore();

		if ( !$this->store instanceOf \SMWSparqlStore ) {
			$this->markTestSkipped( "Requires a SMWSparqlStore instance" );
		}

		$sparqlDatabase = $this->store->getSparqlDatabase();

		if ( !$sparqlDatabase->setConnectionTimeoutInSeconds( 5 )->ping() ) {
			$this->markTestSkipped( "Can't connect to the SparlDatabase" );
		}
	}

	public function testQuerySubjectAfterSparqlDataUpdate() {

		$subject = new DIWikiPage( __METHOD__, NS_MAIN, '' );
		$semanticData = new SemanticData( $subject );

		$this->store->doSparqlDataUpdate( $semanticData );

		$query = new Query(
			new ValueDescription( $subject ),
			false,
			false
		);

		$query->querymode = Query::MODE_INSTANCES;

		$this->assertThatResultsContain(
			$subject,
			$this->store->getQueryResult( $query )
		);
	}

	public function testZeroQueryResultAfterSparqlDataDelete() {

		$property = new DIProperty( __METHOD__ );
		$property->setPropertyTypeId( '_wpg' );

		$semanticData = new SemanticData( new DIWikiPage( __METHOD__, NS_MAIN, '' ) );
		$semanticData->addDataValue( DataValueFactory::getInstance()->newPropertyObjectValue( $property, 'Bar' ) );

		$this->store->doSparqlDataUpdate( $semanticData );

		$description = new SomeProperty(
			$property,
			new ThingDescription()
		);

		$query = new Query(
			$description,
			false,
			false
		);

		$query->querymode = Query::MODE_INSTANCES;

		$this->assertEquals(
			1,
			$this->store->getQueryResult( $query )->getCount()
		);

		$this->assertTrue( $this->store->doSparqlDataDelete( $semanticData->getSubject() ) );

		$this->assertEquals(
			0,
			$this->store->getQueryResult( $query )->getCount()
		);
	}

	private function assertThatResultsContain( $expectedSubject, $queryResult ) {

		$this->assertEquals( 1, $queryResult->getCount() );

		foreach ( $queryResult->getResults() as $result ) {
			$this->assertEquals( $expectedSubject, $result );
		}
	}

}
