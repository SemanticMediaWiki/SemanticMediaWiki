<?php

namespace SMW\Tests\Integration\SPARQLStore;

use SMW\DataValueFactory;
use SMW\DIProperty;
use SMW\DIWikiPage;
use SMW\Exporter\Element\ExpNsResource;
use SMW\SemanticData;
use SMW\Services\ServicesFactory as ApplicationFactory;
use SMW\SPARQLStore\RepositoryRedirectLookup;
use SMW\SPARQLStore\SPARQLStore;
use SMWExporter as Exporter;

/**
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 2.0
 *
 * @author mwjames
 */
class RepositoryRepositoryRedirectLookupActiveConnectionTest extends \PHPUnit\Framework\TestCase {

	private $repositoryConnection;
	private $store;

	protected function setUp(): void {
		$this->store = ApplicationFactory::getInstance()->getStore();

		if ( !$this->store instanceof SPARQLStore ) {
			$this->markTestSkipped( "Skipping test because a SPARQLStore instance is required." );
		}

		$this->repositoryConnection = $this->store->getConnection( 'sparql' );
		$this->repositoryConnection->setConnectionTimeout( 5 );

		if ( !$this->repositoryConnection->ping() ) {
			$this->markTestSkipped( "Can't connect to the RepositoryConnector" );
		}
	}

	/**
	 * @dataProvider resourceProvider
	 */
	public function testRedirectTargetLookupForNonExistingEntry( $expNsResource ) {
		$instance = new RepositoryRedirectLookup( $this->repositoryConnection );
		$instance->reset();

		$exists = null;

		$this->assertSame(
			$expNsResource,
			$instance->findRedirectTargetResource( $expNsResource, $exists )
		);

		$this->assertFalse( $exists );
	}

	public function testRedirectTargetLookupForExistingEntry() {
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
		$exporter = Exporter::getInstance();

		$provider[] = [
			$exporter->newExpNsResourceById( 'rdf', 'type' )
		];

		$provider[] = [
			new ExpNsResource(
				'FooRepositoryRedirectLookup',
				$exporter->getNamespaceUri( 'property' ),
				'property',
				new DIWikiPage( 'FooRepositoryRedirectLookup', SMW_NS_PROPERTY, '' )
			)
		];

		return $provider;
	}

}
