<?php

namespace SMW\Tests\Unit;

use PHPUnit\Framework\TestCase;
use SMW\DataItems\WikiPage;
use SMW\EntityCache;
use Wikimedia\ObjectCache\HashBagOStuff;

/**
 * @covers \SMW\EntityCache
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 3.1
 *
 * @author mwjames
 */
class EntityCacheTest extends TestCase {

	/**
	 * A real in-memory cache backing so the characterization tests exercise the
	 * actual store/retrieve/delete behaviour rather than replaying mock
	 * expectations.
	 */
	private function newCache() {
		return new HashBagOStuff();
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			EntityCache::class,
			new EntityCache( $this->newCache() )
		);
	}

	public function testMakeCacheKey() {
		$instance = new EntityCache( $this->newCache() );

		$this->assertStringContainsString(
			EntityCache::CACHE_NAMESPACE,
			$instance->makeCacheKey( 'Foo' )
		);

		$subject = WikiPage::newFromText( 'Foo' );

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

	public function testMakeCacheKey_SubNamespace() {
		$subject = WikiPage::newFromText( 'Foo' );

		$this->assertStringContainsString(
			'smw:entity:44ab375ee7ebac04b8e4471a70180dc5',
			EntityCache::makeCacheKey( $subject )
		);

		$this->assertStringContainsString(
			'smw:entity:foo:44ab375ee7ebac04b8e4471a70180dc5',
			EntityCache::makeCacheKey( ':foo', $subject )
		);
	}

	public function testSaveFetchContainsDelete() {
		$instance = new EntityCache( $this->newCache() );

		$this->assertFalse( $instance->contains( 'Foo' ) );
		$this->assertFalse( $instance->fetch( 'Foo' ) );

		$instance->save( 'Foo', 'bar' );

		$this->assertTrue( $instance->contains( 'Foo' ) );
		$this->assertSame( 'bar', $instance->fetch( 'Foo' ) );

		$instance->delete( 'Foo' );

		$this->assertFalse( $instance->contains( 'Foo' ) );
		$this->assertFalse( $instance->fetch( 'Foo' ) );
	}

	public function testFetchSub_MissingKeyOrSub() {
		$instance = new EntityCache( $this->newCache() );

		// Absent key: the underlying value is not an array.
		$this->assertFalse( $instance->fetchSub( 'Foo', 'bar' ) );

		$instance->save( 'Foo', [ md5( 'bar' ) => 'Foobar' ] );

		// Present key, missing sub.
		$this->assertFalse( $instance->fetchSub( 'Foo', 'missing' ) );

		// Present key and sub.
		$this->assertSame( 'Foobar', $instance->fetchSub( 'Foo', 'bar' ) );
	}

	public function testSaveSub_MergesIntoExistingRecord() {
		$instance = new EntityCache( $this->newCache() );

		$instance->saveSub( 'Foo', 'bar', '1' );
		$instance->saveSub( 'Foo', 'baz', '2' );

		// Both subs coexist; the second save merges rather than replaces.
		$this->assertSame( '1', $instance->fetchSub( 'Foo', 'bar' ) );
		$this->assertSame( '2', $instance->fetchSub( 'Foo', 'baz' ) );
	}

	public function testOverrideSub_ReplacesWholeRecord() {
		$instance = new EntityCache( $this->newCache() );

		$instance->saveSub( 'Foo', 'bar', '1' );
		$instance->overrideSub( 'Foo', 'baz', '2' );

		// overrideSub discards any prior subs and keeps only the new one.
		$this->assertFalse( $instance->fetchSub( 'Foo', 'bar' ) );
		$this->assertSame( '2', $instance->fetchSub( 'Foo', 'baz' ) );
	}

	public function testDeleteSub_RemovesOnlyTheNamedSub() {
		$instance = new EntityCache( $this->newCache() );

		$instance->saveSub( 'Foo', 'bar', '1' );
		$instance->saveSub( 'Foo', 'baz', '2' );

		$instance->deleteSub( 'Foo', 'bar' );

		$this->assertFalse( $instance->fetchSub( 'Foo', 'bar' ) );
		$this->assertSame( '2', $instance->fetchSub( 'Foo', 'baz' ) );
	}

	public function testAssociate_BuildsAnchorRecord() {
		$subject = WikiPage::newFromText( 'Foo' );
		$instance = new EntityCache( $this->newCache() );

		$instance->associate( $subject, 'assocKey' );

		$anchor = $instance->fetch( EntityCache::makeCacheKey( $subject ) );

		$this->assertSame( $subject->getHash(), $anchor['__subject'] );
		$this->assertTrue( $anchor['__assoc']['assocKey'] );
	}

	public function testAssociate_NoValidSubjectIsNoOp() {
		$instance = new EntityCache( $this->newCache() );

		$instance->associate( 'notASubject', 'assocKey' );

		// No anchor record is created for a non-WikiPage subject.
		$this->assertFalse(
			$instance->contains( EntityCache::makeCacheKey( 'notASubject' ) )
		);
	}

	public function testInvalidate_DeletesAssociatesAndAnchorOnly() {
		$subject = WikiPage::newFromText( 'Foo' );
		$instance = new EntityCache( $this->newCache() );

		$assocKeyOne = $instance->makeCacheKey( 'assoc', 'one' );
		$assocKeyTwo = $instance->makeCacheKey( 'assoc', 'two' );
		$unrelatedKey = $instance->makeCacheKey( 'unrelated' );

		$instance->save( $assocKeyOne, 'a' );
		$instance->save( $assocKeyTwo, 'b' );
		$instance->save( $unrelatedKey, 'u' );
		$instance->associate( $subject, $assocKeyOne );
		$instance->associate( $subject, $assocKeyTwo );

		$anchorKey = EntityCache::makeCacheKey( $subject );
		$this->assertTrue( $instance->contains( $anchorKey ) );

		$instance->invalidate( $subject );

		// Every associated key and the anchor are removed; an unrelated entry
		// survives (the cascade deletes N associates + 1 anchor, no more).
		$this->assertFalse( $instance->contains( $assocKeyOne ) );
		$this->assertFalse( $instance->contains( $assocKeyTwo ) );
		$this->assertFalse( $instance->contains( $anchorKey ) );
		$this->assertTrue( $instance->contains( $unrelatedKey ) );
	}

	public function testInvalidate_NoValidSubjectIsNoOp() {
		$instance = new EntityCache( $this->newCache() );

		$instance->save( 'survivor', 'v' );
		$instance->invalidate( 'notASubject' );

		$this->assertTrue( $instance->contains( 'survivor' ) );
	}

}
