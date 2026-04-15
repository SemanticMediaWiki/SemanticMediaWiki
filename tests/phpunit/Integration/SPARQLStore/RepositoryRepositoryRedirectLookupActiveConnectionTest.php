<?php

namespace SMW\Tests\Integration\SPARQLStore;

use PHPUnit\Framework\TestCase;
use SMW\DataItems\Property;
use SMW\DataItems\WikiPage;
use SMW\DataModel\SemanticData;
use SMW\DataValueFactory;
use SMW\Export\Exporter;
use SMW\Exporter\Element\ExpNsResource;
use SMW\Services\ServicesFactory as ApplicationFactory;
use SMW\SPARQLStore\RepositoryRedirectLookup;

/**
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 2.0
 *
 * @author mwjames
 */
class RepositoryRepositoryRedirectLookupActiveConnectionTest extends TestCase {

	private $repositoryConnection;
	private $store;

	protected function setUp(): void {
		$this->store = ApplicationFactory::getInstance()->getStore();

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
		$property = new Property( 'TestRepositoryRedirectLookup' );

		$semanticData = new SemanticData( new WikiPage( __METHOD__, NS_MAIN ) );

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
				new WikiPage( 'FooRepositoryRedirectLookup', SMW_NS_PROPERTY, '' )
			)
		];

		return $provider;
	}

}
