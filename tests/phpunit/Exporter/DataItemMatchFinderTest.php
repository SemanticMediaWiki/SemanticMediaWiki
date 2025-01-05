<?php

namespace SMW\Tests\Exporter;

use SMW\DIWikiPage;
use SMW\Exporter\DataItemMatchFinder;

/**
 * @covers \SMW\Exporter\DataItemMatchFinder
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 2.4
 *
 * @author mwjames
 */
class DataItemMatchFinderTest extends \PHPUnit\Framework\TestCase {

	public function testCanConstruct() {
		$store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$this->assertInstanceOf(
			DataItemMatchFinder::class,
			new DataItemMatchFinder( $store )
		);
	}

	public function testMatchExpElementOnMatchableWikiNamespaceUri() {
		$dataItem = new DIWikiPage( 'Foo', NS_MAIN, '', 'Bar' );

		$store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$instance = new DataItemMatchFinder(
			$store,
			'http://example.org/id/'
		);

		$expResource = $this->getMockBuilder( '\SMW\Exporter\Element\ExpResource' )
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
		$dataItem = new DIWikiPage( 'Foo', NS_HELP, '', 'Bar' );

		$store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$instance = new DataItemMatchFinder(
			$store,
			'http://example.org/id/'
		);

		$expResource = $this->getMockBuilder( '\SMW\Exporter\Element\ExpResource' )
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
		$dataItem = new DIWikiPage( 'UNKNOWN', NS_MAIN, '', '' );

		$repositoryResult = $this->getMockBuilder( '\SMW\SPARQLStore\QueryEngine\RepositoryResult' )
			->disableOriginalConstructor()
			->getMock();

		$repositoryConnector = $this->getMockBuilder( '\SMW\SPARQLStore\RepositoryConnection' )
			->disableOriginalConstructor()
			->getMock();

		$repositoryConnector->expects( $this->once() )
			->method( 'select' )
			->willReturn( $repositoryResult );

		$store = $this->getMockBuilder( '\SMW\SPARQLStore\SPARQLStore' )
			->disableOriginalConstructor()
			->getMock();

		$store->expects( $this->once() )
			->method( 'getConnection' )
			->willReturn( $repositoryConnector );

		$instance = new DataItemMatchFinder(
			$store,
			'http://example.org/id/'
		);

		$expResource = $this->getMockBuilder( '\SMW\Exporter\Element\ExpResource' )
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
		$store = $this->getMockBuilder( '\SMW\SPARQLStore\SPARQLStore' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new DataItemMatchFinder(
			$store,
			'http://example.org/id/'
		);

		$expResource = $this->getMockBuilder( '\SMW\Exporter\Element\ExpResource' )
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
