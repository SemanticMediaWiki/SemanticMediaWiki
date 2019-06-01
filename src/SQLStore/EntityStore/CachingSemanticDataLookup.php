<?php

namespace SMW\SQLStore\EntityStore;

use Onoi\Cache\Cache;
use Onoi\Cache\NullCache;
use Psr\Log\LoggerAwareTrait;
use RuntimeException;
use SMW\DIProperty;
use SMW\DIWikiPage;
use SMW\RequestOptions;
use SMW\SemanticData;
use SMW\SQLStore\PropertyTableDefinition;
use SMWDataItem as DataItem;

/**
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class CachingSemanticDataLookup {

	use LoggerAwareTrait;

	/**
	 * @var SemanticDataLookup
	 */
	private $semanticDataLookup;

	/**
	 * @var Cache
	 */
	private $cache;

	/**
	 * Cache for SemanticData dataItems, indexed by SMW ID.
	 *
	 * @var array
	 */
	private static $data = [];

	/**
	 * Like SMWSQLStore3::data, but containing flags indicating
	 * completeness of the SemanticData objs.
	 *
	 * @var array
	 */
	private static $state = [];

	/**
	 * >0 while getSemanticData runs, used to prevent nested calls from clearing
	 * the cache while another call runs and is about to fill it with data
	 *
	 * @var int
	 */
	private static $lookupCount = 0;

	/**
	 * @var array
	 */
	private static $prefetch = [];

	/**
	 * @since 3.0
	 *
	 * @param SemanticDataLookup $semanticDataLookup
	 * @param Cache|null $cache
	 */
	public function __construct( SemanticDataLookup $semanticDataLookup, Cache $cache = null ) {
		$this->semanticDataLookup = $semanticDataLookup;
		$this->cache = $cache;

		if ( $this->cache === null ) {
			$this->cache = new NullCache();
		}
	}

	/**
	 * @since 3.0
	 */
	public function lockCache() {
		self::$lookupCount++;
	}

	/**
	 * @since 3.0
	 */
	public function unlockCache() {
		self::$lookupCount--;
	}

	/**
	 * @since 3.0
	 *
	 * @param integer $id
	 */
	public function invalidateCache( $id ) {
		unset( self::$data[$id] );
		unset( self::$state[$id] );
	}

	/**
	 * @since 3.0
	 */
	public static function clear() {
		self::$data = [];
		self::$state = [];
		self::$prefetch = [];
		self::$lookupCount = 0;
	}

	/**
	 * Helper method to make sure there is a cache entry for the data about
	 * the given subject with the given ID.
	 *
	 * @todo The management of this cache should be revisited.
	 *
	 * @since 3.0
	 *
	 * @param int $id
	 * @param DIWikiPage $subject
	 */
	public function initLookupCache( $id, DIWikiPage $subject ) {

		// *** Prepare the cache ***//
		if ( !isset( self::$data[$id] ) ) {
			self::$data[$id] = $this->semanticDataLookup->newStubSemanticData( $subject );
			self::$state[$id] = [];
		}

		// Issue #622
		// If a redirect was cached preceding this request and points to the same
		// subject id ensure that in all cases the requested subject matches with
		// the selected DB id
		if ( self::$data[$id]->getSubject()->getHash() !== $subject->getHash() ) {
			self::$data[$id] = $this->semanticDataLookup->newStubSemanticData( $subject );
			self::$state[$id] = [];
		}

		// It is not so easy to find the sweet spot between cache size and
		// performance gains (both memory and time), The value of 20 was chosen
		// by profiling runtimes for large inline queries and heavily annotated
		// pages. However, things might have changed in the meantime ...
		if ( ( count( self::$data ) > 20 ) && ( self::$lookupCount == 1 ) ) {
			self::$data = [ $id => self::$data[$id] ];
			self::$state = [ $id => self::$state[$id] ];
		}
	}

	/**
	 * Set the semantic data lookup cache to hold exactly the given value for the
	 * given ID.
	 *
	 * @since 3.0
	 *
	 * @param integer $id
	 * @param SemanticData $semanticData
	 */
	public function setLookupCache( $id, SemanticData $semanticData ) {

		self::$data[$id] = $this->semanticDataLookup->newStubSemanticData(
			$semanticData
		);

		self::$state[$id] = $this->semanticDataLookup->getTableUsageInfo(
			$semanticData
		);
	}

	/**
	 * Helper method to make sure there is a cache entry for the data about
	 * the given subject with the given ID.
	 *
	 * @since 3.0
	 *
	 * @param int $id
	 * @param DIWikiPage $subject
	 */
	public function getSemanticDataById( $id ) {

		if ( !isset( self::$data[$id] ) ) {
			throw new RuntimeException( 'Data are not initialized.' );
		}

		return self::$data[$id];
	}

	/**
	 * @since 3.0
	 *
	 * @param PropertyTableDefinition $propertyTableDef
	 * @param DIProperty $property
	 * @param RequestOptions|null $requestOptions
	 *
	 * @return RequestOptions|null
	 */
	public function newRequestOptions( PropertyTableDefinition $propertyTableDef, DIProperty $property, RequestOptions $requestOptions = null ) {
		return $this->semanticDataLookup->newRequestOptions( $propertyTableDef, $property, $requestOptions );
	}

	/**
	 * @since 3.1
	 *
	 * @param array $subjects
	 * @param DataItem $dataItem
	 * @param PropertyTableDefinition $propertyTableDef
	 * @param RequestOptions $requestOptions
	 *
	 * @return []
	 */
	public function prefetchDataFromTable( array $subjects, DataItem $dataItem = null, PropertyTableDefinition $propertyTableDef, RequestOptions $requestOptions = null ) {

		$hash = '';

		if ( $dataItem !== null ) {
			$hash .= $dataItem->getSerialization();
		}

		if (  $requestOptions !== null ) {
			$hash .= $requestOptions->getHash();
		}

		// Use `PREFETCH_FINGERPRINT` if available otherwise make a fingerprint
		// to allow for caching possible repeated requests
		if ( $requestOptions === null || $requestOptions->getOption( RequestOptions::PREFETCH_FINGERPRINT ) === null ) {
			foreach ( $subjects as $subject ) {
				$hash .= $subject->getHash();
			}
		}

		$hash = md5( $hash );

		if ( isset( self::$prefetch[$hash] ) && self::$prefetch[$hash] !== [] ) {
			return self::$prefetch[$hash];
		}

		$data = $this->semanticDataLookup->prefetchDataFromTable(
			$subjects,
			$dataItem,
			$propertyTableDef,
			$requestOptions
		);

		return self::$prefetch[$hash] = $data;
	}

	/**
	 * @since 3.0
	 *
	 * @param integer $id
	 * @param DataItem $dataItem
	 * @param PropertyTableDefinition $propertyTableDef
	 * @param RequestOptions $requestOptions
	 *
	 * @return RequestOptions|null
	 */
	public function fetchSemanticDataFromTable( $id, DataItem $dataItem = null, PropertyTableDefinition $propertyTableDef, RequestOptions $requestOptions = null ) {
		return $this->semanticDataLookup->fetchSemanticDataFromTable( $id, $dataItem, $propertyTableDef, $requestOptions );
	}

	/**
	 * Fetch and cache the data about one subject for one particular table
	 *
	 * @param integer $id
	 * @param DIWikiPage $subject
	 * @param PropertyTableDefinition $propertyTableDef
	 * @param RequestOptions|null $requestOptions
	 *
	 * @return SemanticData
	 */
	public function getSemanticData( $id, DataItem $dataItem = null, PropertyTableDefinition $propertyTableDef, RequestOptions $requestOptions = null ) {

		// Avoid the cache when a request is constrainted
		if ( $requestOptions !== null || !$dataItem instanceof DIWikiPage ) {
			return $this->semanticDataLookup->getSemanticData( $id, $dataItem, $propertyTableDef, $requestOptions );
		}

		return $this->fetchFromCache( $id, $dataItem, $propertyTableDef );
	}

	/**
	 * @since 3.0
	 *
	 * @param DIWikiPage $subject
	 *
	 * @return StubSemanticData
	 */
	public function newStubSemanticData( DIWikiPage $subject ) {
		return $this->semanticDataLookup->newStubSemanticData( $subject );
	}

	private function fetchFromCache( $id, DataItem $dataItem = null, PropertyTableDefinition $propertyTableDef ) {

		// Do not clear the cache when called recursively.
		$this->lockCache();
		$this->initLookupCache( $id, $dataItem );

		// @see also setLookupCache
		$name = $propertyTableDef->getName();

		if ( isset( self::$state[$id][$name] ) ) {
			$this->unlockCache();
			return self::$data[$id];
		}

		$data = $this->semanticDataLookup->fetchSemanticDataFromTable(
			$id,
			$dataItem,
			$propertyTableDef
		);

		foreach ( $data as $d ) {
			self::$data[$id]->addPropertyStubValue( reset( $d ), end( $d ) );
		}

		self::$state[$id][$name] = true;

		$this->unlockCache();

		return self::$data[$id];
	}

}
