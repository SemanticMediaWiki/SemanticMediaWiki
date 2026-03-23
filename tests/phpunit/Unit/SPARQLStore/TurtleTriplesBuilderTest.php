<?php

namespace SMW\Tests\Unit\SPARQLStore;

use PHPUnit\Framework\TestCase;
use SMW\DataItems\WikiPage;
use SMW\DataModel\SemanticData;
use SMW\Export\Exporter;
use SMW\Exporter\Element\ExpNsResource;
use SMW\SPARQLStore\RepositoryRedirectLookup;
use SMW\SPARQLStore\TurtleTriplesBuilder;

/**
 * @covers \SMW\SPARQLStore\TurtleTriplesBuilder
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 2.0
 *
 * @author mwjames
 */
class TurtleTriplesBuilderTest extends TestCase {

	private $repositoryRedirectLookup;

	protected function setUp(): void {
		parent::setUp();

		$this->repositoryRedirectLookup = $this->getMockBuilder( RepositoryRedirectLookup::class )
			->disableOriginalConstructor()
			->getMock();
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			TurtleTriplesBuilder::class,
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
			new WikiPage( 'Foo', NS_MAIN, '' )
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
			new WikiPage( 'Foo', NS_MAIN )
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
