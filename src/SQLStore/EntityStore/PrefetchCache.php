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
	 * Set of bulk prefetch lookups that have already been executed, grouped
	 * by value cache key and subject set fingerprint.
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

		// This value cache key is the contract between prefetch(), isCached(),
		// and getPropertyValues(). It is not shared with lower-level lookup
		// caches.
		//
		// Format:
		// <property-key>[#isChain][#isInverse][#isFirstChain]#valueOptions:<discriminator>
		//
		// Context markers are appended in a fixed order and each marker is
		// self-identifying, so chain, inverse, and value-affecting request
		// options cannot contaminate values cached for the same property key.
		// This is a normalized value identity, not a reversible serialization
		// of RequestOptions.
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

		// RequestOptions::getValueHash() is the single source of truth for which
		// request options change the selected values; see its guard test.
		$key .= '#valueOptions:' . $requestOptions->getValueHash();

		return $key;
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

		$fingerprint = '';

		foreach ( $subjects as $subject ) {
			$fingerprint .= $subject->getHash();
		}

		$valueCacheKey = $this->makeCacheKey( $property, $requestOptions );
		$subjectSetFingerprint = md5( $fingerprint );

		if ( isset( $this->executedPrefetchLookups[$valueCacheKey][$subjectSetFingerprint] ) ) {
			return;
		}

		// Lower-level lookup caches use PREFETCH_FINGERPRINT as the subject-set
		// part of their bulk request identity. Keep that request identity on a
		// clone so it cannot leak into this value cache key or the caller's
		// RequestOptions instance.
		$lookupRequestOptions = clone $requestOptions;
		$lookupRequestOptions->setOption( RequestOptions::PREFETCH_FINGERPRINT, $subjectSetFingerprint );

		$result = $this->prefetchItemLookup->getPropertyValues(
			$subjects,
			$property,
			$lookupRequestOptions
		);

		// Merge in newly fetched subjects without replacing values that are
		// already cached for this value identity.
		$this->valuesByCacheKey[$valueCacheKey] =
			( $this->valuesByCacheKey[$valueCacheKey] ?? [] ) + $result;
		$this->executedPrefetchLookups[$valueCacheKey][$subjectSetFingerprint] = true;
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
		$valueCacheKey = $this->makeCacheKey( $property, $requestOptions );

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

		if ( !isset( $this->valuesByCacheKey[$valueCacheKey][$sid] ) ) {
			return [];
		}

		return array_values( $this->valuesByCacheKey[$valueCacheKey][$sid] );
	}

}
