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
	 *
	 * @return bool
	 */
	public function isCached( Property $property ): bool {
		return isset( $this->cache[$property->getKey()] );
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
	 * @return ?string
	 */
	public static function makeCacheKey( Property $property, RequestOptions $requestOptions ): ?string {
		$key = $property->getKey();

		// Use the .dot notation to distingish it from other prrintouts that
		// use the same property
		if ( $requestOptions->isChain ) {
			$key .= '#' . (string)$requestOptions->isChain;
			$key .= '#' . (string)$property->isInverse();
		}

		// T:P0467, requires an extra identification to ensure the test passes
		// when the lookup is part of the firstChain request
		if ( $requestOptions->isFirstChain ?? false ) {
			$key .= '#' . 'isFirstChain';
		}

		return $key;
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

		$requestOptions->setOption( RequestOptions::PREFETCH_FINGERPRINT, md5( $fingerprint ) );
		$key = $this->makeCacheKey( $property, $requestOptions );

		// Use an aggressive cache strategy to avoid repetitive queries especially
		// when called as part of a printrequest chain
		$lookupKey = md5( $key . '#' . $fingerprint );

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
