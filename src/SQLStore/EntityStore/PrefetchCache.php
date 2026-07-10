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
	 * Fetched values grouped by value cache key, then subject ID.
	 *
	 * @var array
	 */
	private array $valuesByCacheKey = [];

	/**
	 * Set of bulk prefetch lookups that have already been executed.
	 *
	 * This does not store values. It only prevents running the same bulk lookup
	 * twice while values remain stored in $valuesByCacheKey.
	 *
	 * @var array
	 */
	private array $executedPrefetchLookups = [];

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
		return isset( $this->valuesByCacheKey[self::makeCacheKey( $property, $requestOptions )] );
	}

	/**
	 * @since 3.1
	 */
	public function clear(): void {
		$this->valuesByCacheKey = [];
		$this->executedPrefetchLookups = [];
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

		// Value cache key format:
		// <property-key>[#isChain][#isInverse][#isFirstChain][#requestOptions:<hash>]
		//
		// Missing markers represent the default request context. Markers are
		// appended in a fixed order and each marker is self-identifying, so
		// chain, inverse, and printout-local request options cannot contaminate
		// values cached for the same property key. This is a normalized value
		// identity, not a reversible serialization of RequestOptions; null and
		// false default states are represented by the absence of a marker.
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

		$requestOptionsKey = self::makeRequestOptionsCacheKeyPart( $requestOptions );

		if ( $requestOptionsKey !== '' ) {
			$key .= '#requestOptions:' . $requestOptionsKey;
		}

		return $key;
	}

	private static function makeRequestOptionsCacheKeyPart( RequestOptions $requestOptions ): string {
		$hash = self::makeNormalizedRequestOptionsHash( $requestOptions );

		if ( $hash === self::makeNormalizedRequestOptionsHash( new RequestOptions() ) ) {
			return '';
		}

		return md5( $hash );
	}

	private static function makeNormalizedRequestOptionsHash( RequestOptions $requestOptions ): string {
		$requestOptions = clone $requestOptions;

		// Prefetch mode and lower-level SQL lookups mutate these internal flags
		// while executing a lookup. They must not change the identity of the
		// caller's requested values.
		$requestOptions->exclude_limit = false;
		$requestOptions->deleteOption( RequestOptions::PREFETCH_FINGERPRINT );
		$requestOptions->deleteOption( 'NO_GROUPBY' );
		$requestOptions->deleteOption( 'NO_DISTINCT' );
		$requestOptions->deleteOption( 'ORDER BY' );
		$requestOptions->deleteOption( 'GROUP BY' );
		$requestOptions->deleteOption( 'DISTINCT' );

		return (string)$requestOptions->getHash() . '#' . (string)$requestOptions->natural;
	}

	private static function makeSubjectSetKey( array $subjects ): string {
		$subjectHashes = [];

		foreach ( $subjects as $subject ) {
			$subjectHashes[] = $subject->getHash();
		}

		return md5( json_encode( $subjectHashes ) );
	}

	/**
	 * Prefetch related data so getPropertyValues() can return individual
	 * subject values without issuing one lookup per subject.
	 *
	 * @since 3.1
	 *
	 * @param WikiPage[] $subjects
	 * @param Property $property
	 * @param RequestOptions $requestOptions
	 */
	public function prefetch( array $subjects, Property $property, RequestOptions $requestOptions ): void {
		$this->store->getObjectIds()->warmUpCache( $subjects );

		$key = $this->makeCacheKey( $property, $requestOptions );
		$subjectSetKey = self::makeSubjectSetKey( $subjects );

		// Track executed bulk lookups by requested value identity and subject set.
		$executedLookupKey = md5( $key . '#' . $subjectSetKey );

		if ( isset( $this->executedPrefetchLookups[$executedLookupKey] ) ) {
			return;
		}

		// Lower-level lookup caches use PREFETCH_FINGERPRINT as the subject-set
		// part of their bulk request identity. Keep that request identity on a
		// clone so it cannot leak into this value cache key or the caller's
		// RequestOptions instance.
		$lookupRequestOptions = clone $requestOptions;
		$lookupRequestOptions->setOption( RequestOptions::PREFETCH_FINGERPRINT, $subjectSetKey );

		$result = $this->prefetchItemLookup->getPropertyValues(
			$subjects,
			$property,
			$lookupRequestOptions
		);

		$this->valuesByCacheKey[$key] = $result + ( $this->valuesByCacheKey[$key] ?? [] );
		$this->executedPrefetchLookups[$executedLookupKey] = true;
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

		if ( !isset( $this->valuesByCacheKey[$key][$sid] ) ) {
			return [];
		}

		return array_values( $this->valuesByCacheKey[$key][$sid] );
	}

}
