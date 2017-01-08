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

	public function testCanConstruct() {

		$semanticData = $this->getMockBuilder( '\SMW\SemanticData' )
			->disableOriginalConstructor()
			->getMock();

		$repositoryRedirectLookup = $this->getMockBuilder( '\SMW\SPARQLStore\RepositoryRedirectLookup' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			'\SMW\SPARQLStore\TurtleTriplesBuilder',
			new TurtleTriplesBuilder( $semanticData, $repositoryRedirectLookup )
		);
	}

	public function testBuildTriplesForEmptySemanticDataContainer() {

		$expNsResource = new ExpNsResource(
			'Redirect',
			Exporter::getInstance()->getNamespaceUri( 'wiki' ),
			'Redirect'
		);

		$semanticData = new SemanticData( new DIWikiPage( 'Foo', NS_MAIN, '' ) );

		$repositoryRedirectLookup = $this->getMockBuilder( '\SMW\SPARQLStore\RepositoryRedirectLookup' )
			->disableOriginalConstructor()
			->getMock();

		$repositoryRedirectLookup->expects( $this->atLeastOnce() )
			->method( 'findRedirectTargetResource' )
			->will( $this->returnValue( $expNsResource ) );

		$instance = new TurtleTriplesBuilder( $semanticData, $repositoryRedirectLookup );

		$this->assertTrue( $instance->doBuild()->hasTriplesForUpdate() );

		$this->assertInternalType( 'string', $instance->getTriples() );
		$this->assertInternalType( 'array', $instance->getPrefixes() );
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

		$repositoryRedirectLookup = $this->getMockBuilder( '\SMW\SPARQLStore\RepositoryRedirectLookup' )
			->disableOriginalConstructor()
			->getMock();

		$repositoryRedirectLookup->expects( $this->atLeastOnce() )
			->method( 'findRedirectTargetResource' )
			->will( $this->returnValue( $expNsResource ) );

		$instance = new TurtleTriplesBuilder(
			$semanticData,
			$repositoryRedirectLookup
		);

		$instance->setTriplesChunkSize( 1 );

		$this->assertInternalType(
			'array',
			$instance->getChunkedTriples()
		);
	}

}
