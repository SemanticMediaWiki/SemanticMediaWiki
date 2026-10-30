<?php

namespace SMW;

use MapCacheLRU;
use MediaWiki\Title\Title;
use SMW\DataItems\WikiPage;
use Wikimedia\ObjectCache\BagOStuff;

/**
 * Caches values derived from a page so that invalidating the page invalidates
 * all of them, including those of its subobjects.
 *
 * Each value is stored with the version of its page current when the value
 * was generated, and counts as cached only while that version is current.
 * Invalidating a page gives it a new version.
 *
 * @license GPL-2.0-or-later
 * @since 3.1
 *
 * @author mwjames
 */
class EntityCache {

	/**
	 * Repository specific namespace
	 */
	const CACHE_NAMESPACE = 'smw:entity';
	const VERSION = 2;

	/**
	 * TTLs
	 */
	const TTL_SECOND = 1;
	const TTL_MINUTE = 60;
	const TTL_HOUR = 3600;
	const TTL_DAY = 86400; // 24 * 3600
	const TTL_WEEK = 604800; // 7 * 24 * 3600
	const TTL_MONTH = 2592000; // 30 * 24 * 3600
	const TTL_YEAR = 31536000; // 365 * 24 * 3600

	/**
	 * At least the TTL of any value, as a page's values count as outdated once
	 * its version expires
	 */
	private const VERSION_TTL = self::TTL_MONTH;

	/**
	 * @since 3.1
	 *
	 * @param BagOStuff $cache
	 * @param MapCacheLRU $processCache Holds this process's values, keyed by
	 *  the version key of their page, so invalidating a page clears only its values
	 */
	public function __construct(
		private readonly BagOStuff $cache,
		private readonly MapCacheLRU $processCache
	) {
	}

	/**
	 * @since 3.1
	 *
	 * @param string|Title|WikiPage ...$params
	 */
	public function makeKey( ...$params ): string {
		$namespace = self::CACHE_NAMESPACE;

		if ( is_string( $params[0] ) && $params[0][0] === ':' ) {
			$namespace .= array_shift( $params );
		}

		if ( $params[0] instanceof Title ) {
			$params[0] = WikiPage::newFromTitle( $params[0] );
		}

		if ( $params[0] instanceof WikiPage ) {
			$params[0] = $params[0]->getHash();
		}

		$components = explode( ':', $namespace );
		$components[] = md5( json_encode( $params + [ 'version' => self::VERSION ] ) );

		return $this->cache->makeKey( ...$components );
	}

	/**
	 * Returns the value cached under $key, or generates and caches it with
	 * $callback. A generated `false` is not cached.
	 *
	 * A process keeps values it has looked up in memory for up to
	 * BagOStuff::TTL_PROC_SHORT seconds after it got them, so it can take that
	 * long to see an invalidation made by another process.
	 *
	 * @since 7.4.0
	 */
	public function getWithSetCallback( WikiPage|Title $subject, string $key, int $ttl, callable $callback ): mixed {
		return $this->getMultiWithUnionSetCallback(
			[ $key => $subject ],
			$ttl,
			static fn () => [ $key => $callback() ]
		)[$key];
	}

	/**
	 * Like getWithSetCallback() for several values, generating all missing ones
	 * with a single $callback call. $callback receives the subjects of the
	 * missing values and returns a value, or `false`, for each of them, both
	 * keyed by cache key.
	 *
	 * @since 7.4.0
	 *
	 * @param array<string,WikiPage|Title> $subjects Subjects keyed by the cache key of their value
	 *
	 * @return array<string,mixed> Values keyed by cache key
	 */
	public function getMultiWithUnionSetCallback( array $subjects, int $ttl, callable $callback ): array {
		$versionKeys = array_map( fn ( WikiPage|Title $subject ) => $this->makeVersionKey( $subject ), $subjects );
		[ $values, $versions ] = $this->lookUp( $versionKeys );
		$versionKeys = array_diff_key( $versionKeys, $values );

		if ( $versionKeys === [] ) {
			return $values;
		}

		// Before generating, so an invalidation after this point outdates the value.
		// Its source must also be current: data read before the change was
		// committed, or from a replica behind it, is stored under the new version.
		$versions += $this->createVersions( array_diff( $versionKeys, array_keys( $versions ) ) );
		$entries = [];

		foreach ( $callback( array_intersect_key( $subjects, $versionKeys ) ) as $key => $value ) {
			$values[$key] = $value;

			if ( $value !== false ) {
				$entries[$key] = [ $versions[$versionKeys[$key]], $value ];
				$this->processCache->setField( $versionKeys[$key], $key, $value );
			}
		}

		if ( $entries !== [] ) {
			$this->cache->setMulti( $entries, $ttl );
		}

		return $values;
	}

	/**
	 * @since 3.1
	 *
	 * @return mixed The value cached under $key, or false if there is none
	 */
	public function fetch( WikiPage|Title $subject, string $key ): mixed {
		[ $values ] = $this->lookUp( [ $key => $this->makeVersionKey( $subject ) ] );

		return array_key_exists( $key, $values ) ? $values[$key] : false;
	}

	/**
	 * @since 3.1
	 */
	public function invalidate( WikiPage|Title $subject ): void {
		$versionKey = $this->makeVersionKey( $subject );

		$this->cache->set( $versionKey, $this->newVersion(), self::VERSION_TTL );
		$this->processCache->clear( $versionKey );
	}

	/**
	 * For a long-running process that must not use a value that another process
	 * has invalidated in the last BagOStuff::TTL_PROC_SHORT seconds.
	 *
	 * @since 7.4.0
	 */
	public function clearProcessCache(): void {
		$this->processCache->clear();
	}

	/**
	 * @param array<string,string> $versionKeys Version keys keyed by the cache key of their value
	 *
	 * @return array{0: array<string,mixed>, 1: array<string,string>} The cached values found,
	 *  keyed by cache key, and all backend entries read, which hold the versions by version key
	 */
	private function lookUp( array $versionKeys ): array {
		$values = [];

		foreach ( $versionKeys as $key => $versionKey ) {
			if ( $this->processCache->hasField( $versionKey, $key, BagOStuff::TTL_PROC_SHORT ) ) {
				$values[$key] = $this->processCache->getField( $versionKey, $key );
			}
		}

		$versionKeys = array_diff_key( $versionKeys, $values );

		if ( $versionKeys === [] ) {
			return [ $values, [] ];
		}

		// Unique, as a page and its subobjects share a version key
		$stored = $this->cache->getMulti(
			array_merge( array_keys( $versionKeys ), array_values( array_unique( $versionKeys ) ) )
		);

		foreach ( $versionKeys as $key => $versionKey ) {
			$entry = $stored[$key] ?? false;

			if ( isset( $stored[$versionKey] ) && is_array( $entry ) && $entry[0] === $stored[$versionKey] ) {
				$values[$key] = $entry[1];
				$this->processCache->setField( $versionKey, $key, $entry[1] );
			}
		}

		return [ $values, $stored ];
	}

	/**
	 * @param string[] $versionKeys
	 *
	 * @return array<string,string> Versions keyed by version key
	 */
	private function createVersions( array $versionKeys ): array {
		$versions = [];

		foreach ( $versionKeys as $versionKey ) {
			$versions[$versionKey] = $this->newVersion();
		}

		if ( $versions !== [] ) {
			$this->cache->setMulti( $versions, self::VERSION_TTL );
		}

		return $versions;
	}

	private function newVersion(): string {
		return bin2hex( random_bytes( 8 ) );
	}

	private function makeVersionKey( WikiPage|Title $subject ): string {
		if ( $subject instanceof Title ) {
			$subject = WikiPage::newFromTitle( $subject );
		}

		return $this->makeKey( ':version', $subject->asBase() );
	}

}
