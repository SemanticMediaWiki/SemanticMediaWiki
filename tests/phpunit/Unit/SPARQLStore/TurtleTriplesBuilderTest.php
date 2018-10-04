<?php

namespace SMW\Tests\SPARQLStore;

use SMW\DIWikiPage;
use SMW\SemanticData;
use SMW\SPARQLStore\TurtleTriplesBuilder;
use SMWExpNsResource as ExpNsResource;
use SMWExporter as Exporter;

/**
 * @covers \SMW\SPARQLStore\TurtleTriplesBuilder
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.0
 *
 * @author mwjames
 */
class TurtleTriplesBuilderTest extends \PHPUnit_Framework_TestCase {

	private $repositoryRedirectLookup;

	protected function setUp() {
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
			->will( $this->returnValue( $expNsResource ) );

		$instance = new TurtleTriplesBuilder(
			$this->repositoryRedirectLookup
		);

		$instance->doBuildTriplesFrom( $semanticData );

		$this->assertTrue(
			$instance->hasTriples()
		);

		$this->assertInternalType(
			'string',
			$instance->getTriples()
		);

		$this->assertInternalType(
			'array',
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
			->will( $this->returnValue( $expNsResource ) );

		$instance = new TurtleTriplesBuilder(
			$this->repositoryRedirectLookup
		);

		$instance->setTriplesChunkSize( 1 );
		$instance->doBuildTriplesFrom( $semanticData );

		$this->assertInternalType(
			'array',
			$instance->getChunkedTriples()
		);
	}

}
