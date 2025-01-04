<?php

namespace SMW\Tests\SPARQLStore;

use SMW\DIWikiPage;
use SMW\SemanticData;
use SMW\SPARQLStore\TurtleTriplesBuilder;
use SMWExpNsResource as ExpNsResource;
use SMWExporter as Exporter;
use SMW\Tests\PHPUnitCompat;

/**
 * @covers \SMW\SPARQLStore\TurtleTriplesBuilder
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.0
 *
 * @author mwjames
 */
class TurtleTriplesBuilderTest extends \PHPUnit\Framework\TestCase {

	use PHPUnitCompat;

	private $repositoryRedirectLookup;

	protected function setUp(): void {
		parent::setUp();

		$this->repositoryRedirectLookup = $this->getMockBuilder( '\SMW\SPARQLStore\RepositoryRedirectLookup' )
			->disableOriginalConstructor()
			->getMock();
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			'\SMW\SPARQLStore\TurtleTriplesBuilder',
			new TurtleTriplesBuilder( $this->repositoryRedirectLookup )
		);
	}

	public function testBuildTriplesForEmptySemanticDataContainer() {
		$expNsResource = new ExpNsResource(
			'Redirect',
			Exporter::getInstance()->getNamespaceUri( 'wiki' ),
			'Redirect'
		);

		$semanticData = new SemanticData(
			new DIWikiPage( 'Foo', NS_MAIN, '' )
		);

		$this->repositoryRedirectLookup->expects( $this->atLeastOnce() )
			->method( 'findRedirectTargetResource' )
			->willReturn( $expNsResource );

		$instance = new TurtleTriplesBuilder(
			$this->repositoryRedirectLookup
		);

		$instance->doBuildTriplesFrom( $semanticData );

		$this->assertTrue(
			$instance->hasTriples()
		);

		$this->assertIsString(

			$instance->getTriples()
		);

		$this->assertIsArray(

			$instance->getPrefixes()
		);
	}

	public function testChunkedTriples() {
		$expNsResource = new ExpNsResource(
			'Redirect',
			Exporter::getInstance()->getNamespaceUri( 'wiki' ),
			'Redirect'
		);

		$semanticData = new SemanticData(
			new DIWikiPage( 'Foo', NS_MAIN )
		);

		$this->repositoryRedirectLookup->expects( $this->atLeastOnce() )
			->method( 'findRedirectTargetResource' )
			->willReturn( $expNsResource );

		$instance = new TurtleTriplesBuilder(
			$this->repositoryRedirectLookup
		);

		$instance->setTriplesChunkSize( 1 );
		$instance->doBuildTriplesFrom( $semanticData );

		$this->assertIsArray(

			$instance->getChunkedTriples()
		);
	}

}
