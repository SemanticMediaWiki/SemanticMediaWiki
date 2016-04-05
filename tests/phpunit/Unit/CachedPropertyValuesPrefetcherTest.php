<?php

namespace SMW\Tests;

use SMW\CachedPropertyValuesPrefetcher;
use SMW\DIProperty;
use SMW\DIWikiPage;

/**
 * @covers \SMW\CachedPropertyValuesPrefetcher
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.4
 *
 * @author mwjames
 */
class CachedPropertyValuesPrefetcherTest extends \PHPUnit_Framework_TestCase {

	private $store;
	private $blobStore;

	protected function setUp() {
		parent::setUp();

		$this->store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$this->store->expects( $this->any() )
			->method( 'getPropertyValues' )
			->will( $this->returnValue( array() ) );

		$this->blobStore = $this->getMockBuilder( '\Onoi\BlobStore\BlobStore' )
			->disableOriginalConstructor()
			->getMock();
	}

	public function testCanConstruct() {

		$this->assertInstanceOf(
			'\SMW\CachedPropertyValuesPrefetcher',
			new CachedPropertyValuesPrefetcher( $this->store, $this->blobStore )
		);
	}

	public function testGetPropertyValues() {

		$instance = new CachedPropertyValuesPrefetcher(
			$this->store,
			$this->blobStore
		);

		$container = $this->getMockBuilder( '\Onoi\BlobStore\Container' )
			->disableOriginalConstructor()
			->getMock();

		$this->blobStore->expects( $this->once() )
			->method( 'read' )
			->will( $this->returnValue( $container ) );

		$this->assertInternalType(
			'array',
			$instance->getPropertyValues( DIWikiPage::newFromText( 'Foo' ), new DIProperty( 'Bar' ) )
		);
	}

	public function testQueryPropertyValuesFor() {

		$expected = array(
			DIWikiPage::newFromText( 'Foo' )
		);

		$queryResult = $this->getMockBuilder( '\SMWQueryResult' )
			->disableOriginalConstructor()
			->getMock();

		$queryResult->expects( $this->atLeastOnce() )
			->method( 'getResults' )
			->will( $this->returnValue( $expected ) );

		$this->store->expects( $this->any() )
			->method( 'getQueryResult' )
			->will( $this->returnValue( $queryResult ) );

		$query = $this->getMockBuilder( '\SMWQuery' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new CachedPropertyValuesPrefetcher(
			$this->store,
			$this->blobStore
		);

		$this->assertEquals(
			$expected,
			$instance->queryPropertyValuesFor( $query )
		);
	}

	public function testGetPropertyValuesFromCache() {

		$container = $this->getMockBuilder( '\Onoi\BlobStore\Container' )
			->disableOriginalConstructor()
			->getMock();

		$container->expects( $this->atLeastOnce() )
			->method( 'has' )
			->will( $this->returnValue( true ) );

		$container->expects( $this->once() )
			->method( 'get' )
			->with( $this->stringContains( 'Bar:123' ) )
			->will( $this->returnValue( 1001 ) );

		$this->blobStore->expects( $this->atLeastOnce() )
			->method( 'read' )
			->will( $this->returnValue( $container ) );

		$instance = new CachedPropertyValuesPrefetcher(
			$this->store,
			$this->blobStore
		);

		$this->assertEquals(
			1001,
			$instance->getPropertyValues( DIWikiPage::newFromText( 'Foo#123' ), new DIProperty( 'Bar' ) )
		);
	}

}
