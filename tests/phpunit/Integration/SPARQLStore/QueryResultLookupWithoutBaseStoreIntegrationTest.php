<?php

namespace SMW\Tests\Integration\SPARQLStore;

use SMW\DataValueFactory;
use SMW\DIProperty;
use SMW\DIWikiPage;
use SMW\Query\Language\NamespaceDescription as NamespaceDescription;
use SMW\Query\Language\SomeProperty as SomeProperty;
use SMW\Query\Language\ThingDescription as ThingDescription;
use SMW\Query\Language\ValueDescription as ValueDescription;
use SMW\SPARQLStore\SPARQLStore;
use SMW\StoreFactory;
use SMW\Subobject;
use SMW\Tests\Utils\SemanticDataFactory;
use SMW\Tests\Utils\Validators\QueryResultValidator;
use SMWDINumber as DINumber;
use SMWQuery as Query;

/**
 *
 * @group SMW
 * @group SMWExtension
 * @group semantic-mediawiki-integration
 * @group semantic-mediawiki-sparql
 * @group semantic-mediawiki-query
 *
 * @license GNU GPL v2+
 * @since 2.0
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

		if ( !$this->store instanceof SPARQLStore ) {
			$this->markTestSkipped( "Requires a SPARQLStore instance" );
		}

		$sparqlDatabase = $this->store->getConnection();

		if ( !$sparqlDatabase->setConnectionTimeoutInSeconds( 5 )->ping() ) {
			$this->markTestSkipped( "Can't connect to the SPARQL database" );
		}

		$sparqlDatabase->deleteAll();

		$this->queryResultValidator = new QueryResultValidator();
		$this->semanticDataFactory = new SemanticDataFactory();
		$this->dataValueFactory = DataValueFactory::getInstance();
	}

	public function testQuerySubjects_afterUpdatingSemanticData() {

		$semanticData = $this->semanticDataFactory->newEmptySemanticData( __METHOD__ );

		$this->store->doSparqlDataUpdate( $semanticData );

		$query = new Query(
			new ValueDescription( $semanticData->getSubject() ),
			false,
			false
		);

		$query->querymode = Query::MODE_INSTANCES;

		$this->queryResultValidator->assertThatQueryResultHasSubjects(
			$semanticData->getSubject(),
			$this->store->getQueryResult( $query )
		);
	}

	public function testQueryZeroResults_afterSubjectRemoval() {

		$semanticData = $this->semanticDataFactory->newEmptySemanticData( __METHOD__ );

		$property = new DIProperty( __METHOD__ );
		$property->setPropertyTypeId( '_wpg' );

		$semanticData->addDataValue(
			$this->dataValueFactory->newDataValueByProperty( $property, 'Bar' )
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

		$this->assertTrue(
			$this->store->doSparqlDataDelete( $semanticData->getSubject() )
		);

		$this->assertEquals(
			0,
			$this->store->getQueryResult( $query )->getCount()
		);
	}

	/**
	 * @see http://semantic-mediawiki.org/wiki/Help:Selecting_pages#Restricting_results_to_a_namespace
	 */
	public function testQuerySubjects_onNamspaceRestrictedCondition() {

		$subjectInHelpNamespace = new DIWikiPage( __METHOD__, NS_HELP, '' );

		$semanticData = $this->semanticDataFactory
			->setSubject( $subjectInHelpNamespace )
			->newEmptySemanticData();

		$property = new DIProperty( 'SomePageTypePropertyForNamespaceAnnotation' );
		$property->setPropertyTypeId( '_wpg' );

		$semanticData->addDataValue(
			$this->dataValueFactory->newDataValueByProperty( $property, 'Bar' )
		);

		$this->store->doSparqlDataUpdate( $semanticData );

		$query = new Query(
			new NamespaceDescription( NS_HELP ),
			false,
			false
		);

		$query->querymode = Query::MODE_INSTANCES;

		$this->queryResultValidator->assertThatQueryResultHasSubjects(
			$subjectInHelpNamespace,
			$this->store->getQueryResult( $query )
		);

		$this->assertTrue(
			$this->store->doSparqlDataDelete( $semanticData->getSubject() )
		);

		$this->assertSame(
			0,
			$this->store->getQueryResult( $query )->getCount()
		);
	}

	public function testQuerySubobjects_afterUpdatingWithEmptyContainerAllAssociatedEntitiesGetRemovedFromGraph() {

		$semanticData = $this->semanticDataFactory->newEmptySemanticData( __METHOD__ );

		$subobject = new Subobject( $semanticData->getSubject()->getTitle() );
		$subobject->setEmptyContainerForId( 'SubobjectToTestReferenceAfterUpdate' );

		$property = new DIProperty( 'SomeNumericPropertyToCompareReference' );
		$property->setPropertyTypeId( '_num' );

		$dataItem = new DINumber( 99999 );

		$subobject->addDataValue(
			$this->dataValueFactory->newDataValueByItem( $dataItem, $property )
		);

		$semanticData->addPropertyObjectValue(
			$subobject->getProperty(),
			$subobject->getContainer()
		);

		$this->store->doSparqlDataUpdate( $semanticData );

		$description = new SomeProperty(
			$property,
			new ValueDescription( $dataItem, null, SMW_CMP_EQ )
		);

		$query = new Query(
			$description,
			false,
			false
		);

		$query->querymode = Query::MODE_INSTANCES;

		$this->assertSame(
			1,
			$this->store->getQueryResult( $query )->getCount()
		);

		$this->queryResultValidator->assertThatQueryResultHasSubjects(
			$subobject->getSemanticData()->getSubject(),
			$this->store->getQueryResult( $query )
		);

		$this->store->doSparqlDataUpdate(
			$this->semanticDataFactory->newEmptySemanticData( __METHOD__ )
		);

		$this->assertSame(
			0,
			$this->store->getQueryResult( $query )->getCount()
		);
	}

}
