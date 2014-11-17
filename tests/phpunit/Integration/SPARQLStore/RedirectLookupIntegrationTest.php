<?php

namespace SMW\Tests\Integration\SPARQLStore;

use SMW\SPARQLStore\SPARQLStore;
use SMW\SPARQLStore\RedirectLookup;
use SMW\DIWikiPage;
use SMW\DIProperty;
use SMW\StoreFactory;
use SMW\DataValueFactory;
use SMW\SemanticData;

use SMWExpNsResource as ExpNsResource;
use SMWExporter as Exporter;

/**
 *
 * @group SMW
 * @group SMWExtension
 * @group semantic-mediawiki-integration
 * @group semantic-mediawiki-sparql
 *
 * @license GNU GPL v2+
 * @since 2.0
 *
 * @author mwjames
 */
class RedirectLookupIntegrationTest extends \PHPUnit_Framework_TestCase {

	private $sparqlDatabase;
	private $store;

	protected function setUp() {

		$this->store = StoreFactory::getStore();

		if ( !$this->store instanceOf SPARQLStore ) {
			$this->markTestSkipped( "Requires a SPARQLStore instance" );
		}

		$this->sparqlDatabase = $this->store->getConnection();

		if ( !$this->sparqlDatabase->setConnectionTimeoutInSeconds( 5 )->ping() ) {
			$this->markTestSkipped( "Can't connect to the SparlDatabase" );
		}
	}

	/**
	 * @dataProvider resourceProvider
	 */
	public function testRedirectTragetLookupForNonExistingEntry( $expNsResource ) {

		$instance = new RedirectLookup( $this->sparqlDatabase );
		$instance->clear();

		$exists = null;

		$this->assertSame(
			$expNsResource,
			$instance->findRedirectTargetResource( $expNsResource, $exists )
		);

		$this->assertFalse( $exists );
	}

	public function testRedirectTragetLookupForExistingEntry() {

		$property = new DIProperty( 'RedirectLookupForExistingEntry' );

		$semanticData = new SemanticData( new DIWikiPage( __METHOD__, NS_MAIN, '' ) );
		$semanticData->addDataValue( DataValueFactory::getInstance()->newPropertyObjectValue( $property, 'Bar' ) );

		$this->store->doSparqlDataUpdate( $semanticData );

		$expNsResource = new ExpNsResource(
			'RedirectLookupForExistingEntry',
			Exporter::getNamespaceUri( 'property' ),
			'property'
		);

		$instance = new RedirectLookup( $this->sparqlDatabase );
		$instance->clear();

		$exists = null;

		$this->assertSame(
			$expNsResource,
			$instance->findRedirectTargetResource( $expNsResource, $exists )
		);

		$this->assertTrue( $exists );
	}

	public function resourceProvider() {

		$provider[] = array(
			Exporter::getSpecialNsResource( 'rdf', 'type' )
		);

		$provider[] = array(
			new ExpNsResource(
				'FooRedirectLookup',
				Exporter::getNamespaceUri( 'property' ),
				'property',
				new DIWikiPage( 'FooRedirectLookup', SMW_NS_PROPERTY, '' )
			)
		);

		return $provider;
	}

}
