<?php

namespace SMW\SQLStore\EntityStore;

use SMW\SQLStore\SQLStore;
use SMW\SQLStore\PropertyTableDefinition as TableDefinition;
use SMWDataItem as DataItem;
use SMW\DIWikiPage;
use SMW\DIProperty;
use SMW\RequestOptions;
use SMW\DataTypeRegistry;
use RuntimeException;
use SMW\MediaWiki\LinkBatch;

/**
 * @license GNU GPL v2
 * @since 3.1
 *
 * @author mwjames
 */
class PrefetchCache {

	/**
	 * @var SQLStore
	 */
	private $store;

	/**
	 * @var PrefetchItemLookup
	 */
	private $prefetchItemLookup;

	/**
	 * @var []
	 */
	private $cache = [];

	/**
	 * @var []
	 */
	private $lookupCache = [];

	/**
	 * @since 3.1
	 *
	 * @param SQLStore $store
	 * @param PrefetchItemLookup $prefetchItemLookup
	 */
	public function __construct( SQLStore $store, PrefetchItemLookup $prefetchItemLookup ) {
		$this->store = $store;
		$this->prefetchItemLookup = $prefetchItemLookup;
	}

	/**
	 * @since 3.1
	 *
	 * @param DIProperty $property
	 *
	 * @return boolean
	 */
	public function isCached( DIProperty $property, RequestOptions $requestOptions ) {
		$lookupKey = $this->makeCacheKey( $property, $requestOptions );
		return isset( $this->lookupCache[$lookupKey] );
	}

	/**
	 * @since 3.1
	 */
	public function clear() {
		$this->cache = [];
		$this->lookupCache = [];
	}

	/**
	 * @since 3.1
	 *
	 * @param DIProperty $property
	 * @param RequestOptions $requestOptions
	 */
	public static function makeCacheKey( DIProperty $property, RequestOptions $requestOptions ) {

		$key = '';

		// Use the .dot notation to distingish it from other prrintouts that
		// use the same property
		if ( isset( $requestOptions->isChain ) && $requestOptions->isChain ) {
			$key = $requestOptions->isChain . '.';
		}

		$key .= ( $property->isInverse() ? '-' : '' ) . $property->getKey();

		return $key;
	}

	/**
	 * Prefetch related data into the cache in order for the `LookupCache::get`
	 * to return the individual data.
	 *
	 * @since 3.1
	 *
	 * @param DIWikiPage[] $subjects
	 * @param DIProperty $property
	 * @param RequestOptions $requestOptions
	 */
	public function prefetch( array $subjects, DIProperty $property, RequestOptions $requestOptions ) {

		$lookupKey = $this->makeCacheKey( $property, $requestOptions );
		if ( isset( $this->lookupCache[$lookupKey] ) ) {
			return;
		}

		# Load from previous lookup
		if ( isset( $requestOptions->isChain ) && $requestOptions->isChain ) {
			if ( !isset( $this->lookupCache[$requestOptions->isChain] ) ) {
				throw new \Exception();
			}
			$subjects = $this->lookupCache[$requestOptions->isChain];
		}

		$fingerprint = '';
		$this->store->getObjectIds()->warmUpCache( $subjects );
		foreach ( $subjects as $subject ) {
			$fingerprint .= $subject->getHash();
		}
		$requestOptions->setOption( RequestOptions::PREFETCH_FINGERPRINT, md5( $fingerprint ) );

		$result = $this->prefetchItemLookup->getPropertyValues(
			$subjects,
			$property,
			$requestOptions
		);

		# Register we looked up to this chained property to be able to restart from these values
		$this->lookupCache[$lookupKey] = [];
		foreach ( $result as $rk => $rv ) {
			$this->lookupCache[$lookupKey] = array_merge( $this->lookupCache[$lookupKey], array_values( $rv ) );
		}

		# Put all results of this property in the cache
		$key = ( $property->isInverse() ? '-' : '' ) . $property->getKey();
		if ( isset( $this->cache[$key] ) ) {
			$this->cache[$key] = array_replace( $result, $this->cache[$key] );
		} else {
			$this->cache[$key] = $result;
		}
	}

	/**
	 * @since 3.1
	 *
	 * @param DIWikiPage $subject
	 * @param DIProperty $property
	 * @param RequestOptions $requestOptions
	 *
	 * @return []
	 */
	public function getPropertyValues( DIWikiPage $subject, DIProperty $property, RequestOptions $requestOptions ) {

		$key = ( $property->isInverse() ? '-' : '' ) . $property->getKey();

		$sid = $this->store->getObjectIds()->getSMWPageID(
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
