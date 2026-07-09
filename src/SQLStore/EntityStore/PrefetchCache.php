<?php

namespace SMW\SQLStore\EntityStore;

use SMW\DataItems\Property;
use SMW\DataItems\WikiPage;
use SMW\RequestOptions;
use SMW\SQLStore\SQLStore;

/**
 * @license GPL-2.0-or-later
 * @since 3.1
 *
 * @author mwjames
 */
class PrefetchCache {

	/**
	 * @var array
	 */
	private $cache = [];

	/**
	 * @var array
	 */
	private array $lookupCache = [];

	/**
	 * @since 3.1
	 */
	public function __construct(
		private readonly SQLStore $store,
		private readonly PrefetchItemLookup $prefetchItemLookup,
	) {
	}

	/**
	 * @since 3.1
	 *
	 * @param Property $property
	 * @param RequestOptions $requestOptions
	 *
	 * @return bool
	 */
	public function isCached( Property $property, RequestOptions $requestOptions ): bool {
		return isset( $this->cache[self::makeCacheKey( $property, $requestOptions )] );
	}

	/**
	 * @since 3.1
	 */
	public function clear(): void {
		$this->cache = [];
		$this->lookupCache = [];
	}

	/**
	 * @since 3.1
	 *
	 * @param Property $property
	 * @param RequestOptions $requestOptions
	 *
	 * @return string
	 */
	public static function makeCacheKey( Property $property, RequestOptions $requestOptions ): string {
		$key = $property->getKey();

		// Cache key format:
		// <property-key>[#isChain][#isInverse][#isFirstChain]
		//
		// Missing markers represent the default request context. Markers are
		// appended in a fixed order and each marker is self-identifying, so
		// chain and inverse lookups for the same property key cannot contaminate
		// each other by changing the meaning of another key segment.
		if ( $requestOptions->isChain ) {
			$key .= '#' . 'isChain';
		}

		if ( $property->isInverse() ) {
			$key .= '#' . 'isInverse';
		}

		// T:P0467, requires an extra identification to ensure the test passes
		// when the lookup is part of the firstChain request
		if ( $requestOptions->isFirstChain ?? false ) {
			$key .= '#' . 'isFirstChain';
		}

		return $key;
	}

	private static function makeRequestOptionsHash( RequestOptions $requestOptions ): string {
		$requestOptions = clone $requestOptions;

		// The prefetch fingerprint is added below for lower-level caches to
		// identify the subject set. It must not make this lookup cache key
		// change when the same RequestOptions instance is prefetched again.
		$requestOptions->deleteOption( RequestOptions::PREFETCH_FINGERPRINT );

		return (string)$requestOptions->getHash();
	}

	/**
	 * Prefetch related data into the cache in order for the `LookupCache::get`
	 * to return the individual data.
	 *
	 * @since 3.1
	 *
	 * @param WikiPage[] $subjects
	 * @param Property $property
	 * @param RequestOptions $requestOptions
	 */
	public function prefetch( array $subjects, Property $property, RequestOptions $requestOptions ): void {
		$fingerprint = '';
		$this->store->getObjectIds()->warmUpCache( $subjects );

		foreach ( $subjects as $subject ) {
			$fingerprint .= $subject->getHash();
		}

		$requestOptionsHash = self::makeRequestOptionsHash( $requestOptions );
		$requestOptions->setOption( RequestOptions::PREFETCH_FINGERPRINT, md5( $fingerprint ) );
		$key = $this->makeCacheKey( $property, $requestOptions );

		// Use an aggressive cache strategy to avoid repetitive queries especially
		// when called as part of a printrequest chain. Request options belong to
		// the lookup identity because printout-local options such as +order or
		// +limit can produce a different value list for the same property and
		// subject set.
		$lookupKey = md5( $key . '#' . $fingerprint . '#' . $requestOptionsHash );

		if ( isset( $this->lookupCache[$lookupKey] ) ) {
			return;
		}

		$result = $this->prefetchItemLookup->getPropertyValues(
			$subjects,
			$property,
			$requestOptions
		);

		$this->cache[$key] = $result + ( $this->cache[$key] ?? [] );
		$this->lookupCache[$lookupKey] = true;
	}

	/**
	 * @since 3.1
	 *
	 * @param WikiPage $subject
	 * @param Property $property
	 * @param RequestOptions $requestOptions
	 *
	 * @return array
	 */
	public function getPropertyValues( WikiPage $subject, Property $property, RequestOptions $requestOptions ): array {
		$key = $this->makeCacheKey( $property, $requestOptions );

		// 0 is the default ID of the subject, if it already has an ID,
		// there is no need to do a DB query for the ID.
		$sid = $subject->getId() !== 0
			? $subject->getId()
			: $this->store->getObjectIds()->getSMWPageID(
				$subject->getDBkey(),
				$subject->getNamespace(),
				$subject->getInterwiki(),
				$subject->getSubobjectName(),
				true
			);

		if ( !isset( $this->cache[$key][$sid] ) ) {
			return [];
		}

		return array_values( $this->cache[$key][$sid] );
	}

}
