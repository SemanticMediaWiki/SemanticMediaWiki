<?php

namespace SMW\Tests\Unit\SQLStore\EntityStore;

use PHPUnit\Framework\TestCase;
use SMW\Cache\InMemoryLruCache;
use SMW\DataItems\Property;
use SMW\DataItems\WikiPage;
use SMW\SQLStore\EntityStore\IdCacheManager;

/**
 * @covers \SMW\SQLStore\EntityStore\IdCacheManager
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since   3.0
 *
 * @author mwjames
 */
class IdCacheManagerTest extends TestCase {

	private $caches;

	protected function setUp(): void {
		$this->caches = [
			'entity.id' => new InMemoryLruCache(),
			'entity.sort' => new InMemoryLruCache(),
			'entity.lookup' => new InMemoryLruCache(),
			'propertytable.hash' => new InMemoryLruCache()
		];
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			IdCacheManager::class,
			new IdCacheManager( $this->caches )
		);
	}

	public function testComputeSha1() {
		$result = IdCacheManager::computeSha1( [] );

		$this->assertIsString( $result );
		$this->assertSame( 20, strlen( $result ), 'SHA-1 raw binary should be 20 bytes' );
	}

	/**
	 * Golden master for the persisted `smw_hash` format. `computeSha1` output is
	 * written verbatim to the indexed `smw_object_ids.smw_hash` column
	 * (`EntityIdManager`) and recomputed and compared for equality on every
	 * lookup (`EntityIdFinder`), so these bytes must never change. A failure here
	 * means the on-disk hash format drifted and would orphan every existing row
	 * and trigger a store-wide rewrite. The algorithm is frozen byte-for-byte:
	 * `sha1( json_encode( $args ), true )`.
	 *
	 * @dataProvider computeSha1GoldenProvider
	 */
	public function testComputeSha1GoldenValues( array|string $args, string $expectedHex ) {
		$result = IdCacheManager::computeSha1( $args );

		$this->assertSame( 20, strlen( $result ), 'raw binary sha1 must be 20 bytes' );
		$this->assertSame( $expectedHex, bin2hex( $result ) );
	}

	public static function computeSha1GoldenProvider(): array {
		return [
			'empty array' => [ [], '97d170e1550eee4afc0af065b78cda302a97674c' ],
			'title ns0' => [ [ 'Foo', 0, '', '' ], 'ebb1b47f7cf43a5a58d3c6cc58f3c3bb8b9246e6' ],
			'title ns14' => [ [ 'Foo', 14, '', '' ], 'ba82960e597903f175fe3bcd337107a8561794f5' ],
			'title ns102' => [ [ 'Foo', 102, '', '' ], '909d8ab26ea49adb7e1b106bc47602050d07d19f' ],
			'title with interwiki' => [ [ 'Foo', 0, 'iw', '' ], '02d4cb9bfd6bca88085334899bfdedd7362c4ffa' ],
			'title with subobject' => [ [ 'Foo', 0, '', '_QUERY1' ], 'b8bd3168279b9fb7166cf470b0fe0c2f183b2d4e' ],
			'underscore dbkey' => [ [ 'Foo_Bar', 0, '', '' ], 'b57057ac8a6b3c1581452e19d6f1fc9d74d7e358' ],
			'multibyte title' => [ [ "F\u{00F3}o", 0, '', '' ], '932cfaa8d156c512b9ae15b2ce7a27c70928dd41' ],
			'string scalar arg' => [ 'foo', 'd465e627f9946f2fa0d2dc0fc04e5385bc6cd46d' ],
		];
	}

	/**
	 * The `(int)` namespace cast applied at every `computeSha1` callsite is
	 * load-bearing: `json_encode( 0 )` and `json_encode( "0" )` differ, so a
	 * refactor that dropped the cast would silently change the persisted hash.
	 */
	public function testComputeSha1NamespaceCastIsLoadBearing() {
		$intNamespace = IdCacheManager::computeSha1( [ 'Foo', 0, '', '' ] );
		$stringNamespace = IdCacheManager::computeSha1( [ 'Foo', '0', '', '' ] );

		$this->assertNotSame(
			bin2hex( $intNamespace ),
			bin2hex( $stringNamespace ),
			'int and string namespace must not collide; the (int) cast matters'
		);
		$this->assertSame( '50c2318b07f3d9e418a8f45106d1d43ee08b450b', bin2hex( $stringNamespace ) );
	}

	/**
	 * `WikiPage::getSha1` duplicates the `computeSha1` algorithm and feeds the
	 * same persisted `smw_hash` column, so the two must stay byte-identical.
	 *
	 * @covers \SMW\SQLStore\EntityStore\IdCacheManager::computeSha1
	 * @covers \SMW\DataItems\WikiPage::getSha1
	 * @dataProvider wikiPageSha1Provider
	 */
	public function testWikiPageGetSha1MatchesComputeSha1( string $dbkey, int $namespace, string $interwiki, string $subobject ) {
		$dataItem = new WikiPage( $dbkey, $namespace, $interwiki, $subobject );

		$this->assertSame(
			bin2hex( IdCacheManager::computeSha1( [ $dbkey, $namespace, $interwiki, $subobject ] ) ),
			bin2hex( $dataItem->getSha1() )
		);
	}

	public static function wikiPageSha1Provider(): array {
		return [
			'plain' => [ 'Foo', 0, '', '' ],
			'category ns' => [ 'Foo', 14, '', '' ],
			'interwiki' => [ 'Foo', 0, 'iw', '' ],
			'subobject' => [ 'Foo', 0, '', '_QUERY1' ],
		];
	}

	/**
	 * `Property::getSha1` likewise mirrors `computeSha1` for the property
	 * namespace and must stay byte-identical.
	 *
	 * @covers \SMW\SQLStore\EntityStore\IdCacheManager::computeSha1
	 * @covers \SMW\DataItems\Property::getSha1
	 */
	public function testPropertyGetSha1MatchesComputeSha1() {
		$property = new Property( 'Foo' );

		$this->assertSame(
			bin2hex( IdCacheManager::computeSha1( [ 'Foo', SMW_NS_PROPERTY, '', '' ] ) ),
			bin2hex( $property->getSha1() )
		);
	}

	public function testGet() {
		$instance = new IdCacheManager( $this->caches );

		$this->assertInstanceOf(
			InMemoryLruCache::class,
			$instance->get( 'entity.sort' )
		);
	}

	public function testGetThrowsException() {
		$instance = new IdCacheManager( $this->caches );

		$this->expectException( '\RuntimeException' );
		$instance->get( 'foo' );
	}

	public function testGetId() {
		$instance = new IdCacheManager( $this->caches );

		$instance->setCache( 'foo', 0, '', '', 42, 'bar' );

		$this->assertEquals(
			42,
			$instance->getId( new WikiPage( 'foo', NS_MAIN ) )
		);

		$this->assertEquals(
			42,
			$instance->getId( [ 'foo', 0, '', '' ] )
		);

		$this->assertFalse(
						$instance->getId( [ 'foo', '0', '', '' ] )
		);

		$this->assertEquals(
			42,
			$instance->getId( $instance->computeSha1( [ 'foo', 0, '', '' ] ) )
		);
	}

	public function testGetSort() {
		$instance = new IdCacheManager( $this->caches );

		$instance->setCache( 'foo', 0, '', '', 42, 'bar' );

		$this->assertEquals(
			'bar',
			$instance->getSort( $instance->computeSha1( [ 'foo', 0, '', '' ] ) )
		);

		$this->assertEquals(
			'bar',
			$instance->getSort( [ 'foo', 0, '', '' ] )
		);
	}

	public function testDeleteCache() {
		$instance = new IdCacheManager( $this->caches );

		$instance->setCache( 'foo', 0, '', '', '42', 'bar' );

		$this->assertEquals(
			42,
			$instance->getId( [ 'foo', 0, '', '' ] )
		);

		$instance->deleteCache( 'foo', 0, '', '' );

		$this->assertFalse(
						$instance->getId( [ 'foo', '0', '', '' ] )
		);

		$this->assertFalse(
						$instance->getSort( [ 'foo', 0, '', '' ] )
		);
	}

	public function testHasCache() {
		$instance = new IdCacheManager( $this->caches );

		$instance->setCache( 'foo', 0, '', '', '42', 'bar' );

		$this->assertFalse(
						$instance->hasCache( [ 'foo', 0, '', '' ] )
		);

		$this->assertTrue(
						$instance->hasCache( $instance->computeSha1( [ 'foo', 0, '', '' ] ) )
		);
	}

	public function testDeleteCacheById() {
		$instance = new IdCacheManager( $this->caches );
		$instance->setCache( 'foo', 0, '', '', '42', 'bar' );

		$key = IdCacheManager::computeSha1( [ 'foo', 0, '', '' ] );
		$this->assertNotFalse(
			$this->caches['entity.id']->fetch( $key ),
			'precondition: the id is cached under entity.id'
		);

		$instance->deleteCacheById( 42 );

		$this->assertFalse(
			$this->caches['entity.id']->fetch( $key ),
			'deleteCacheById removes the entity.id entry'
		);
	}

	public function testSetCacheOnTitleWithSpace_ThrowsException() {
		$instance = new IdCacheManager( $this->caches );

		$this->expectException( '\RuntimeException' );
		$instance->setCache( 'foo bar', '', '', '', '', '' );
	}

	public function testSetCacheOnTitleAsArray_ThrowsException() {
		$instance = new IdCacheManager( $this->caches );

		$this->expectException( '\RuntimeException' );
		$instance->setCache( [ 'foo bar' ], '', '', '', '', '' );
	}

}
