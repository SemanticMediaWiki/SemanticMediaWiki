<?php

namespace SMW\Tests\Unit\Exporter;

use PHPUnit\Framework\TestCase;
use SMW\DataItems\WikiPage;
use SMW\Exporter\DataItemMatchFinder;
use SMW\Exporter\Element\ExpResource;
use SMW\SPARQLStore\QueryEngine\RepositoryResult;
use SMW\SPARQLStore\RepositoryConnection;
use SMW\SPARQLStore\SPARQLStore;
use SMW\Store;

/**
 * @covers \SMW\Exporter\DataItemMatchFinder
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 2.4
 *
 * @author mwjames
 */
class DataItemMatchFinderTest extends TestCase {

	public function testCanConstruct() {
		$store = $this->getMockBuilder( Store::class )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$this->assertInstanceOf(
			DataItemMatchFinder::class,
			new DataItemMatchFinder( $store )
		);
	}

	public function testMatchExpElementOnMatchableWikiNamespaceUri() {
		$dataItem = new WikiPage( 'Foo', NS_MAIN, '', 'Bar' );

		$store = $this->getMockBuilder( Store::class )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$instance = new DataItemMatchFinder(
			$store,
			'http://example.org/id/'
		);

		$expResource = $this->getMockBuilder( ExpResource::class )
			->disableOriginalConstructor()
			->getMock();

		$expResource->expects( $this->once() )
			->method( 'getUri' )
			->willReturn( 'http://example.org/id/Foo#Bar' );

		$this->assertEquals(
			$dataItem,
			$instance->matchExpElement( $expResource )
		);
	}

	public function testMatchExpElementOnMatchableWikiNamespaceUriWithHelpWikiNs() {
		$dataItem = new WikiPage( 'Foo', NS_HELP, '', 'Bar' );

		$store = $this->getMockBuilder( Store::class )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$instance = new DataItemMatchFinder(
			$store,
			'http://example.org/id/'
		);

		$expResource = $this->getMockBuilder( ExpResource::class )
			->disableOriginalConstructor()
			->getMock();

		$expResource->expects( $this->once() )
			->method( 'getUri' )
			->willReturn( 'http://example.org/id/Help:Foo#Bar' );

		$this->assertEquals(
			$dataItem,
			$instance->matchExpElement( $expResource )
		);
	}

	public function testMatchExpElementOnUnmatchableWikiNamespaceUri() {
		$dataItem = new WikiPage( 'UNKNOWN', NS_MAIN, '', '' );

		$repositoryResult = $this->getMockBuilder( RepositoryResult::class )
			->disableOriginalConstructor()
			->getMock();

		$repositoryConnector = $this->getMockBuilder( RepositoryConnection::class )
			->disableOriginalConstructor()
			->getMock();

		$repositoryConnector->expects( $this->once() )
			->method( 'select' )
			->willReturn( $repositoryResult );

		$store = $this->getMockBuilder( SPARQLStore::class )
			->disableOriginalConstructor()
			->getMock();

		$store->expects( $this->once() )
			->method( 'getConnection' )
			->willReturn( $repositoryConnector );

		$instance = new DataItemMatchFinder(
			$store,
			'http://example.org/id/'
		);

		$expResource = $this->getMockBuilder( ExpResource::class )
			->disableOriginalConstructor()
			->getMock();

		$expResource->expects( $this->once() )
			->method( 'getUri' )
			->willReturn( 'http://foo.org/id/Foo#Bar' );

		$this->assertEquals(
			$dataItem,
			$instance->matchExpElement( $expResource )
		);
	}

	public function testTryToFindDataItemOnInvalidUri() {
		$store = $this->getMockBuilder( SPARQLStore::class )
			->disableOriginalConstructor()
			->getMock();

		$instance = new DataItemMatchFinder(
			$store,
			'http://example.org/id/'
		);

		$expResource = $this->getMockBuilder( ExpResource::class )
			->disableOriginalConstructor()
			->getMock();

		$expResource->expects( $this->once() )
			->method( 'getUri' )
			->willReturn( '_node1abjt1k9bx17' );

		$this->assertNull(
			$instance->matchExpElement( $expResource )
		);
	}

}
