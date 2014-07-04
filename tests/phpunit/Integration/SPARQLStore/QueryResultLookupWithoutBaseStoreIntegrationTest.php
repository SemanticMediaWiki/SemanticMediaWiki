<?php

namespace SMW\Tests\Integration\SPARQLStore;

use SMW\Tests\Util\QueryResultValidator;
use SMW\Tests\Util\SemanticDataFactory;

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
	private $queryResultValidator;
	private $semanticDataFactory;
	private $dataValueFactory;

	protected function setUp() {

		$this->store = StoreFactory::getStore();

		if ( !$this->store instanceOf \SMWSparqlStore ) {
			$this->markTestSkipped( "Requires a SMWSparqlStore instance" );
		}

		$sparqlDatabase = $this->store->getSparqlDatabase();

		if ( !$sparqlDatabase->setConnectionTimeoutInSeconds( 5 )->ping() ) {
			$this->markTestSkipped( "Can't connect to the SparlDatabase" );
		}

		$this->queryResultValidator = new QueryResultValidator();
		$this->semanticDataFactory = new SemanticDataFactory();
		$this->dataValueFactory = DataValueFactory::getInstance();
	}

	public function testQuerySubjectAfterSparqlDataUpdate() {

		$semanticData = $this->semanticDataFactory->newEmptySemanticData( __METHOD__ );

		$this->store->doSparqlDataUpdate( $semanticData );

		$query = new Query(
			new ValueDescription( $semanticData->getSubject() ),
			false,
			false
		);

		$query->querymode = Query::MODE_INSTANCES;

		$this->queryResultValidator->assertThatQueryResultHasSubjects(
			array( $semanticData->getSubject() ),
			$this->store->getQueryResult( $query )
		);
	}

	public function testZeroQueryResultAfterSparqlDataDelete() {

		$semanticData = $this->semanticDataFactory->newEmptySemanticData( __METHOD__ );

		$property = new DIProperty( __METHOD__ );
		$property->setPropertyTypeId( '_wpg' );

		$semanticData->addDataValue(
			$this->dataValueFactory->newPropertyObjectValue( $property, 'Bar' )
		);

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

}
