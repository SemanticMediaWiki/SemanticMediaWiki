<?php

namespace SMW\Cache;

use Onoi\Cache\Cache;
use SMW\ApplicationFactory;
use SMW\DIWikiPage;
use SMW\SemanticData;
use SMW\HashBuilder;
use Title;
use RuntimeException;

/**
 * Storing and retrieving of a serialized SemanticData from/to a cache
 *
 * @license GNU GPL v2+
 * @since 2.2
 *
 * @author mwjames
 */
class SemanticDataCache {

	/**
	 * Identifies an auxiliary of the key part that is stable but can be modfied
	 * in order for all keys to be rebuild if necessary
	 */
	const auxiliaryKeyModifier = '01';

	/**
	 * @var SemanticDataCache
	 */
	private static $instance = null;

	/**
	 * @var Cache
	 */
	private $cache;

	/**
	 * @var string
	 */
	private $cachePrefix = '';

	/**
	 * @since 2.2
	 *
	 * @param Cache $cache
	 * @param string $cachePrefix
	 */
	public function __construct( Cache $cache, $cachePrefix ) {
		$this->cache = $cache;
		$this->cachePrefix = $cachePrefix;
	}

	/**
	 * @since 2.2
	 *
	 * @return SemanticDataCache
	 */
	public static function getInstance() {

		if ( self::$instance === null ) {

			// The memory-cache is used as fallback and it is expected that
			// the instance is being setup using `setCache`
			$cache = ApplicationFactory::getInstance()->newCacheFactory()->newFixedInMemoryCache( 500 );

			self::$instance = new self(
				$cache,
				$GLOBALS['wgCachePrefix'] === false ? wfWikiID() : $GLOBALS['wgCachePrefix']
			);
		}

		return self::$instance;
	}

	/**
	 * @since 2.2
	 */
	public static function clear() {
		self::$instance = null;
	}

	/**
	 * @since 2.2
	 *
	 * @param Cache $cache
	 */
	public function setCache( Cache $cache ) {
		$this->cache = $cache;
	}

	/**
	 * @since 2.2
	 *
	 * @param SemanticData $semanticData
	 */
	public function save( SemanticData $semanticData ) {

		// By default getUpdateIdentifier contains the LatestRevID which means
		// that any new edit (with a new revId) will inevitably replace
		// the data for the subject hash therefore no extra means are necessary
		// to invalidate an entry where the verification will made against the
		// revId
		$data = array(
			'updateIdentifier' => $semanticData->getUpdateIdentifier(),
			'data' => ApplicationFactory::getInstance()->newSerializerFactory()->serialize( $semanticData )
		);

		$this->cache->save(
			$this->getPageCacheKey( $semanticData->getSubject() ),
			$data
		);
	}

	/**
	 * @since 2.2
	 *
	 * @param Title $title
	 *
	 * @param boolean
	 */
	public function has( Title $title ) {

		$data = $this->cache->fetch(
			$this->getPageCacheKey( DIWikiPage::newFromTitle( $title ) )
		);

		if ( $data !== false && $data['updateIdentifier'] === $title->getLatestRevID() ) {
			return true;
		}

		return false;
	}

	/**
	 * @since 2.2
	 *
	 * @param Title $title
	 *
	 * @return SemanticData
	 * @throws RuntimeException
	 */
	public function get( Title $title ) {

		$data = $this->cache->fetch(
			$this->getPageCacheKey( DIWikiPage::newFromTitle( $title ) )
		);

		if ( $data !== false && $data['data'] !== '' ) {
			$data = ApplicationFactory::getInstance()->newSerializerFactory()->deserialize( $data['data'] );
		}

		if ( $data instanceOf SemanticData ) {
			return $data;
		}

		throw new RuntimeException( "Something went wrong during de-serialization" );
	}

	private function getPageCacheKey( DIWikiPage $page ) {
		return $this->getCachePrefix() . 'sem-cache:' . md5( HashBuilder::getHashIdForDiWikiPage( $page ) . self::auxiliaryKeyModifier );
	}

	private function getCachePrefix() {
		return $this->cachePrefix . ':' . 'smw:';
	}

}
