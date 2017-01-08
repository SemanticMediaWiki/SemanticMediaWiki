<?php

namespace SMW\Tests\Integration\SPARQLStore;

use SMW\DataValueFactory;
use SMW\DIProperty;
use SMW\DIWikiPage;
use SMW\SemanticData;
use SMW\SPARQLStore\RepositoryRedirectLookup;
use SMW\SPARQLStore\SPARQLStore;
use SMW\ApplicationFactory;
use SMWExpNsResource as ExpNsResource;
use SMWExporter as Exporter;

/**
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.0
 *
 * @author mwjames
 */
class RepositoryRepositoryRedirectLookupActiveConnectionTest extends \PHPUnit_Framework_TestCase {

	private $repositoryConnection;
	private $store;

	protected function setUp() {

		$this->store = ApplicationFactory::getInstance()->getStore();

		if ( !$this->store instanceof SPARQLStore ) {
			$this->markTestSkipped( "Skipping test because a SPARQLStore instance is required." );
		}

		$this->repositoryConnection = $this->store->getConnection( 'sparql' );

		if ( !$this->repositoryConnection->setConnectionTimeoutInSeconds( 5 )->ping() ) {
			$this->markTestSkipped( "Can't connect to the RepositoryConnector" );
		}
	}

	/**
	 * @dataProvider resourceProvider
	 */
	public function testRedirectTragetLookupForNonExistingEntry( $expNsResource ) {

		$instance = new RepositoryRedirectLookup( $this->repositoryConnection );
		$instance->reset();

		$exists = null;

		$this->assertSame(
			$expNsResource,
			$instance->findRedirectTargetResource( $expNsResource, $exists )
		);

		$this->assertFalse( $exists );
	}

	public function testRedirectTragetLookupForExistingEntry() {

		$property = new DIProperty( 'TestRepositoryRedirectLookup' );

		$semanticData = new SemanticData( new DIWikiPage( __METHOD__, NS_MAIN ) );

		$semanticData->addDataValue(
			DataValueFactory::getInstance()->newDataValueByProperty( $property, 'Bar' )
		);

		$this->store->doSparqlDataUpdate( $semanticData );

		$expNsResource = new ExpNsResource(
			'TestRepositoryRedirectLookup',
			Exporter::getInstance()->getNamespaceUri( 'property' ),
			'property'
		);

		$instance = new RepositoryRedirectLookup( $this->repositoryConnection );
		$instance->reset();

		$exists = null;

		$this->assertSame(
			$expNsResource,
			$instance->findRedirectTargetResource( $expNsResource, $exists )
		);

		$this->assertTrue( $exists );
	}

	public function resourceProvider() {

		$provider[] = array(
			Exporter::getInstance()->getSpecialNsResource( 'rdf', 'type' )
		);

		$provider[] = array(
			new ExpNsResource(
				'FooRepositoryRedirectLookup',
				Exporter::getInstance()->getNamespaceUri( 'property' ),
				'property',
				new DIWikiPage( 'FooRepositoryRedirectLookup', SMW_NS_PROPERTY, '' )
			)
		);

		return $provider;
	}

}
