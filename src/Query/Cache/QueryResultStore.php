<?php

namespace SMW\Query\Cache;

use MapCacheLRU;
use Wikimedia\ObjectCache\BagOStuff;

/**
 * Durable store for cached query results, the SMW-owned replacement for the
 * former bundled `onoi/blob-store`.
 *
 * Two tiers: a request-scoped {@link MapCacheLRU} fast tier holding the raw
 * (unserialized) payload to avoid repeated unserialization, over a
 * cross-request {@link BagOStuff} slow tier that holds the serialized payload.
 * A backend hit is promoted into the fast tier.
 *
 * The cache key (`{namespacePrefix}:{namespace}:{id}`) and the `serialize()`
 * encoding are preserved byte-for-byte from the former blob store so entries it
 * wrote remain readable. `delete()` reimplements the enumerable `@linkedList`
 * bulk-purge: it reads the container, hard-deletes every linked id and then the
 * anchor from both tiers (N+1 true deletes), never a WAN tombstone/check-key,
 * because SMW invalidates query results by precise deletion.
 *
 * @license GPL-2.0-or-later
 * @since 7.0.0
 *
 * @author mwjames
 */
class QueryResultStore {

	private string $namespace;
	private BagOStuff $cache;
	private MapCacheLRU $internalCache;
	private string $namespacePrefix = 'blobstore';
	private bool $usageState = true;
	private int $expiry = 0;

	/**
	 * @since 7.0.0
	 *
	 * @param string $namespace
	 * @param BagOStuff $cache Cross-request slow tier.
	 * @param MapCacheLRU|null $internalCache Request-scoped fast tier.
	 */
	public function __construct( string $namespace, BagOStuff $cache, ?MapCacheLRU $internalCache = null ) {
		$this->namespace = $namespace;
		$this->cache = $cache;
		$this->internalCache = $internalCache ?? new MapCacheLRU( 500 );
	}

	/**
	 * @since 7.0.0
	 *
	 * @param string $namespacePrefix
	 */
	public function setNamespacePrefix( $namespacePrefix ): void {
		$this->namespacePrefix = $namespacePrefix;
	}

	/**
	 * @since 7.0.0
	 *
	 * @param int $expiry
	 */
	public function setExpiryInSeconds( $expiry ): void {
		$this->expiry = (int)$expiry;
	}

	/**
	 * @since 7.0.0
	 *
	 * @param bool $usageState
	 */
	public function setUsageState( $usageState ): void {
		$this->usageState = (bool)$usageState;
	}

	/**
	 * @since 7.0.0
	 */
	public function canUse(): bool {
		return $this->usageState;
	}

	/**
	 * @since 7.0.0
	 *
	 * @param string $id
	 */
	public function exists( $id ): bool {
		return $this->cache->get( $this->getKey( $id ) ) !== false;
	}

	/**
	 * @since 7.0.0
	 *
	 * @param string $id
	 */
	public function read( $id ): QueryResultContainer {
		$key = $this->getKey( $id );

		if ( $this->internalCache->has( $key ) ) {
			$data = $this->internalCache->get( $key );
		} else {
			$blob = $this->cache->get( $key );

			if ( $blob !== false ) {
				$data = unserialize( $blob );
				$this->internalCache->set( $key, $data );
			} else {
				$data = [];
			}
		}

		$container = new QueryResultContainer( $key, (array)$data );
		$container->setExpiryInSeconds( $this->expiry );

		return $container;
	}

	/**
	 * @since 7.0.0
	 */
	public function save( QueryResultContainer $container ): void {
		// The container id is already the composite key (assigned by read()).
		$this->internalCache->set( $container->getId(), $container->getData() );

		$this->cache->set(
			$container->getId(),
			serialize( $container->getData() ),
			$container->getExpiry()
		);
	}

	/**
	 * @since 7.0.0
	 *
	 * @param string $id
	 */
	public function delete( $id ): void {
		$container = $this->read( $id );
		$keys = [];

		foreach ( $container->getLinkedList() as $lid ) {
			$linkedKey = $this->getKey( $lid );
			$this->cache->delete( $linkedKey );
			$keys[] = $linkedKey;
		}

		$anchorKey = $this->getKey( $id );
		$this->cache->delete( $anchorKey );
		$keys[] = $anchorKey;

		// Evict the anchor and every linked id from the fast tier as well, so a
		// later read in the same request cannot return a stale promoted entry.
		$this->internalCache->clear( $keys );
	}

	private function getKey( $id ): string {
		return $this->namespacePrefix . ':' . $this->namespace . ':' . $id;
	}

}
