<?php

namespace SMW\Tests\Integration\SPARQLStore;

use SMW\SemanticData;
use SMW\DIWikiPage;
use SMW\StoreFactory;

use SMWValueDescription as ValueDescription;
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
class SimpleQueryLookupWithoutBaseStoreIntegrationTest extends \PHPUnit_Framework_TestCase {

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

	private function assertThatResultsContain( $expectedSubject, $queryResult ) {

		$this->assertEquals( 1, $queryResult->getCount() );

		foreach ( $queryResult->getResults() as $result ) {
			$this->assertEquals( $expectedSubject, $result );
		}
	}

}
