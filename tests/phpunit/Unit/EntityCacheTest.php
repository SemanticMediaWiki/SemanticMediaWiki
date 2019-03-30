<?php

namespace SMW\Tests;

use SMW\EntityCache;
use SMW\DIWikiPage;

/**
 * @covers \SMW\EntityCache
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.1
 *
 * @author mwjames
 */
class EntityCacheTest extends \PHPUnit_Framework_TestCase {

	private $cache;

	protected function setUp() {

		$this->cache = $this->getMockBuilder( '\Onoi\Cache\Cache' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();
	}

	public function testCanConstruct() {

		$this->assertInstanceOf(
			EntityCache::class,
			new EntityCache( $this->cache )
		);
	}

	public function testMakeCacheKey() {

		$instance = new EntityCache(
			$this->cache
		);

		$this->assertContains(
			EntityCache::CACHE_NAMESPACE,
			$instance->makeCacheKey( 'Foo' )
		);

		$subject = DIWikiPage::newFromText( 'Foo' );

		$this->assertEquals(
			$instance->makeCacheKey( $subject ),
			$instance->makeCacheKey( 'Foo#0##' )
		);

		$this->assertEquals(
			$instance->makeCacheKey( $subject->getTitle() ),
			$instance->makeCacheKey( 'Foo#0##' )
		);

		$this->assertEquals(
			EntityCache::makeCacheKey( $subject->getTitle() ),
			$instance->makeKey( 'Foo#0##' )
		);
	}

	public function testContains() {

		$this->cache->expects( $this->once() )
			->method( 'contains' )
			->with( $this->equalTo( 'Foo' ) );

		$instance = new EntityCache(
			$this->cache
		);

		$instance->contains( 'Foo' );
	}

	public function testFetch() {

		$this->cache->expects( $this->once() )
			->method( 'fetch' )
			->with( $this->equalTo( 'Foo' ) )
			->will( $this->returnValue( 'bar' ) );

		$instance = new EntityCache(
			$this->cache
		);

		$instance->fetch( 'Foo' );
	}

	public function testSave() {

		$this->cache->expects( $this->once() )
			->method( 'save' )
			->with(
				$this->equalTo( 'Foo' ),
				$this->equalTo( 'bar' ) );

		$instance = new EntityCache(
			$this->cache
		);

		$instance->save( 'Foo', 'bar' );
	}

	public function testDelete() {

		$this->cache->expects( $this->once() )
			->method( 'delete' )
			->with( $this->equalTo( 'Foo' ) );

		$instance = new EntityCache(
			$this->cache
		);

		$instance->delete( 'Foo' );
	}

	public function testFetchSub() {

		$this->cache->expects( $this->once() )
			->method( 'fetch' )
			->with( $this->equalTo( 'Foo' ) )
			->will( $this->returnValue( [ md5( 'bar' ) => 'Foobar' ] ) );

		$instance = new EntityCache(
			$this->cache
		);

		$this->assertEquals(
			'Foobar',
			$instance->fetchSub( 'Foo', 'bar' )
		);
	}

	public function tesSaveSub() {

		$this->cache->expects( $this->once() )
			->method( 'fetch' )
			->with( $this->equalTo( 'Foo' ) )
			->will( $this->returnValue( [ md5( 'bar' ) => 'Foobar' ] ) );

		$this->cache->expects( $this->once() )
			->method( 'save' )
			->with(
				$this->equalTo( 'Foo' ),
				$this->equalTo( [ md5( 'bar' ) => '123' ] ) );

		$instance = new EntityCache(
			$this->cache
		);

		$instance->saveSub( 'Foo', 'bar', '123' );
	}

	public function tesOverrideSub() {

		$this->cache->expects( $this->once() )
			->method( 'save' )
			->with(
				$this->equalTo( 'Foo' ),
				$this->equalTo( [ md5( 'bar' ) => '123' ] ) );

		$instance = new EntityCache(
			$this->cache
		);

		$instance->overrideSub( 'Foo', 'bar', '123' );
	}

	public function tesDeleteSub() {

		$this->cache->expects( $this->once() )
			->method( 'fetch' )
			->with( $this->equalTo( 'Foo' ) )
			->will( $this->returnValue( [ md5( 'bar' ) => 'Foobar', md5( 'foobar' ) => '123' ] ) );

		$this->cache->expects( $this->once() )
			->method( 'save' )
			->with(
				$this->equalTo( 'Foo' ),
				$this->equalTo( [ md5( 'foobar' ) => '123' ] ) );

		$instance = new EntityCache(
			$this->cache
		);

		$instance->deleteSub( 'Foo', 'bar' );
	}

	public function testAssociate() {

		$subject = DIWikiPage::newFromText( 'Foo' );

		$expected = [
			md5( 'bar' ) => 'Foobar',
			'__assoc' => [ 'Foo' => true ],
			'__subject' => $subject->getHash()
		];

		$this->cache->expects( $this->once() )
			->method( 'fetch' )
			->will( $this->returnValue( [ md5( 'bar' ) => 'Foobar' ] ) );

		$this->cache->expects( $this->once() )
			->method( 'save' )
			->with(
				$this->stringContains( 'smw:entity:44ab375ee7ebac04b8e4471a70180dc5' ),
				$this->equalTo( $expected ) );

		$instance = new EntityCache(
			$this->cache
		);

		$instance->associate( $subject, 'Foo' );
	}

	public function testAssociate_NoValidSubject() {

		$this->cache->expects( $this->never() )
			->method( 'fetch' );

		$instance = new EntityCache(
			$this->cache
		);

		$instance->associate( 'notASubject', 'Foo' );
	}

	public function testInvalidate() {

		$subject = DIWikiPage::newFromText( 'Foo' );

		$this->cache->expects( $this->once() )
			->method( 'fetch' )
			->will( $this->returnValue( [ md5( 'bar' ) => 'Foobar', '__assoc' => [ 'Foo' => true ] ] ) );

		$this->cache->expects( $this->at( 1 ) )
			->method( 'delete' )
			->with(	$this->stringContains( 'Foo' ) );

		$this->cache->expects( $this->at( 2 ) )
			->method( 'delete' )
			->with(	$this->stringContains( 'smw:entity:44ab375ee7ebac04b8e4471a70180dc5' ) );

		$instance = new EntityCache(
			$this->cache
		);

		$instance->invalidate( $subject );
	}

	public function testInvalidate_NoValidSubject() {

		$this->cache->expects( $this->never() )
			->method( 'fetch' );

		$instance = new EntityCache(
			$this->cache
		);

		$instance->invalidate( 'notASubject' );
	}

}
