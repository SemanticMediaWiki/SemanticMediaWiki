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
	public function isCached( DIProperty $property ) {
		return isset( $this->cache[$property->getKey()] );
	}

	/**
	 * @since 3.1
	 */
	public function clear() {
		$this->cache = [];
	}

	/**
	 * @since 3.1
	 *
	 * @param DIProperty $property
	 * @param RequestOptions $requestOptions
	 */
	public static function makeCacheKey( DIProperty $property, RequestOptions $requestOptions ) {

		$key = $property->getKey();

		// Use the .dot notation to distingish it from other prrintouts that
		// use the same property
		if ( isset( $requestOptions->isChain ) && $requestOptions->isChain ) {
			$key .= $requestOptions->isChain;
		}

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

		$key = $this->makeCacheKey( $property, $requestOptions );
		$this->cache[$key] = $result;
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

		$key = $this->makeCacheKey( $property, $requestOptions );

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
