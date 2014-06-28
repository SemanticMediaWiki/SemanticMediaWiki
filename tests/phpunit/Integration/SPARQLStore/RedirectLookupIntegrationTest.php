<?php

namespace SMW\Tests\SPARQLStore;

use SMW\SPARQLStore\RedirectLookup;
use SMW\DIWikiPage;

use SMW\StoreFactory;

use SMWExpNsResource as ExpNsResource;
use SMWExporter as Exporter;

/**
 * @ingroup Test
 *
 * @group SMW
 * @group SMWExtension
 *
 * @license GNU GPL v2+
 * @since 1.9.3
 *
 * @author mwjames
 */
class RedirectLookupIntegrationTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @var SparqlDatabase
	 */
	private $sparqlDatabase = null;

	protected function setUp() {

		$store = StoreFactory::getStore();

		if ( !$store instanceOf \SMWSparqlStore ) {
			$this->markTestSkipped(
				"Requires a SMWSparqlStore instance"
			);
		}

		$this->sparqlDatabase = $store->getSparqlDatabase();

		if ( !$this->sparqlDatabase->setConnectionTimeoutInSeconds( 5 )->ping() ) {
			$this->markTestSkipped( 'SparlDatabase connector is not accessible' );
		}
	}

	/**
	 * @dataProvider resourceProvider
	 */
	public function testRedirectTragetLookupOnRealConnection( $expNsResource ) {

		$instance = new RedirectLookup( $this->sparqlDatabase );
		$exists = null;

		$this->assertSame(
			$expNsResource,
			$instance->findRedirectTargetResource( $expNsResource, $exists )
		);

		$this->assertFalse( $exists );
	}

	public function resourceProvider() {

		$provider[] = array(
			Exporter::getSpecialNsResource( 'rdf', 'type' )
		);

		$provider[] = array(
			new ExpNsResource(
				'Foo',
				Exporter::getNamespaceUri( 'property' ),
				'property',
				new DIWikiPage( 'Foo', SMW_NS_PROPERTY, '' )
			)
		);

		return $provider;
	}

}
