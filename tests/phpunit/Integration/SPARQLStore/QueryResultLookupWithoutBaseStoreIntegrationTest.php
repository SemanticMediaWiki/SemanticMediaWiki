<?php

namespace SMW\Tests\Integration\SPARQLStore;

use PHPUnit\Framework\TestCase;
use SMW\DataItems\Number;
use SMW\DataItems\Property;
use SMW\DataItems\WikiPage;
use SMW\DataModel\Subobject;
use SMW\DataValueFactory;
use SMW\Query\Language\NamespaceDescription;
use SMW\Query\Language\SomeProperty;
use SMW\Query\Language\ThingDescription;
use SMW\Query\Language\ValueDescription;
use SMW\Query\Query;
use SMW\Services\ServicesFactory as ApplicationFactory;
use SMW\Tests\Utils\SemanticDataFactory;
use SMW\Tests\Utils\Validators\QueryResultValidator;

/**
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 2.0
 *
 * @author mwjames
 */
class QueryResultLookupWithoutBaseStoreIntegrationTest extends TestCase {

	private $store = null;
	private $queryResultValidator;
	private $semanticDataFactory;
	private $dataValueFactory;

	protected function setUp(): void {
		$this->store = ApplicationFactory::getInstance()->getStore();

		$repositoryConnection = $this->store->getConnection( 'sparql' );
		$repositoryConnection->setConnectionTimeout( 5 );

		if ( !$repositoryConnection->ping() ) {
			$this->markTestSkipped( "Can't connect to the SPARQL repository" );
		}

		$repositoryConnection->deleteAll();

		$this->queryResultValidator = new QueryResultValidator();
		$this->semanticDataFactory = new SemanticDataFactory();
		$this->dataValueFactory = DataValueFactory::getInstance();

		ApplicationFactory::getInstance()->singleton( 'ResultCache' )->disableCache();
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

		$property = new Property( __METHOD__ );
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

		$this->assertSame(
			1,
			$this->store->getQueryResult( $query )->getCount()
		);

		$this->assertTrue(
			$this->store->doSparqlDataDelete( $semanticData->getSubject() )
		);

		$this->assertSame(
			0,
			$this->store->getQueryResult( $query )->getCount()
		);
	}

	/**
	 * @see http://semantic-mediawiki.org/wiki/Help:Selecting_pages#Restricting_results_to_a_namespace
	 */
	public function testQuerySubjects_onNamspaceRestrictedCondition() {
		$subjectInHelpNamespace = new WikiPage( __METHOD__, NS_HELP, '' );

		$semanticData = $this->semanticDataFactory
			->setSubject( $subjectInHelpNamespace )
			->newEmptySemanticData();

		$property = new Property( 'SomePageTypePropertyForNamespaceAnnotation' );
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

		$property = new Property( 'SomeNumericPropertyToCompareReference' );
		$property->setPropertyTypeId( '_num' );

		$dataItem = new Number( 99999 );

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
