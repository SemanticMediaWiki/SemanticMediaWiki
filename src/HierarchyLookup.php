<?php

namespace SMW;

use Onoi\Cache\Cache;
use SMW\Store;
use Psr\Log\LoggerInterface;
use Psr\Log\LoggerAwareInterface;

/**
 * @license GNU GPL v2+
 * @since 2.3
 *
 * @author mwjames
 */
class HierarchyLookup implements LoggerAwareInterface {

	const POOLCACHE_ID = 'hierarchy.lookup';

	/**
	 * @var Store
	 */
	private $store = null;

	/**
	 * @var Cache|null
	 */
	private $cache = null;

	/**
	 * @var LoggerInterface
	 */
	private $logger;

	/**
	 * Use 0 to disable the hierarchy lookup
	 *
	 * @var integer
	 */
	private $subcategoryDepth = 10;

	/**
	 * Use 0 to disable the hierarchy lookup
	 *
	 * @var integer
	 */
	private $subpropertyDepth = 10;

	/**
	 * @since 2.3
	 *
	 * @param Store $store
	 * @param Cache $cache
	 */
	public function __construct( Store $store, Cache $cache ) {
		$this->store = $store;
		$this->cache = $cache;
	}

	/**
	 * @see LoggerAwareInterface::setLogger
	 *
	 * @since 2.5
	 *
	 * @param LoggerInterface $logger
	 */
	public function setLogger( LoggerInterface $logger ) {
		$this->logger = $logger;
	}

	/**
	 * @since 2.3
	 *
	 * @param integer $subcategoryDepth
	 */
	public function setSubcategoryDepth( $subcategoryDepth ) {
		$this->subcategoryDepth = (int)$subcategoryDepth;
	}

	/**
	 * @since 2.3
	 *
	 * @param integer $subpropertyDepth
	 */
	public function setSubpropertyDepth( $subpropertyDepth ) {
		$this->subpropertyDepth = (int)$subpropertyDepth;
	}

	/**
	 * @since 2.3
	 *
	 * @param DIProperty $property
	 *
	 * @return boolean
	 */
	public function hasSubproperty( DIProperty $property ) {

		if ( $this->subpropertyDepth < 1 ) {
			return false;
		}

		$requestOptions = new RequestOptions();
		$requestOptions->limit = 1;

		$result = $this->lookup(
			'_SUBP',
			$property->getKey(),
			$property->getDiWikiPage(),
			$requestOptions
		);

		return $result !== array();
	}

	/**
	 * @since 2.3
	 *
	 * @param DIWikiPage $category
	 *
	 * @return boolean
	 */
	public function hasSubcategory( DIWikiPage $category ) {

		if ( $this->subcategoryDepth < 1 ) {
			return false;
		}

		$requestOptions = new RequestOptions();
		$requestOptions->limit = 1;

		$result = $this->lookup(
			'_SUBC',
			$category->getDBKey(),
			$category,
			$requestOptions
		);

		return $result !== array();
	}

	/**
	 * @since 2.3
	 *
	 * @param DIProperty $property
	 *
	 * @return DIWikiPage[]|[]
	 */
	public function findSubpropertyList( DIProperty $property ) {

		if ( $this->subpropertyDepth < 1 ) {
			return false;
		}

		return $this->lookup( '_SUBP', $property->getKey(), $property->getDiWikiPage(), new RequestOptions() );
	}

	/**
	 * @since 2.3
	 *
	 * @param DIWikiPage $category
	 *
	 * @return DIWikiPage[]|[]
	 */
	public function findSubcategoryList( DIWikiPage $category ) {

		if ( $this->subcategoryDepth < 1 ) {
			return [];
		}

		return $this->lookup( '_SUBC', $category->getDBKey(), $category, new RequestOptions() );
	}

	private function lookup( $id, $key, DIWikiPage $subject, $requestOptions ) {

		$key = $id . '#' . $key . '#' . md5( $requestOptions->getHash() );

		if ( $this->cache->contains( $key ) ) {
			return $this->cache->fetch( $key );
		}

		$result = $this->store->getPropertySubjects(
			new DIProperty( $id ),
			$subject,
			$requestOptions
		);

		$this->cache->save(
			$key,
			$result
		);

		$this->log( __METHOD__ . " {$id} and " . $subject->getDBKey() );

		return $result;
	}

	private function log( $message, $context = array() ) {

		if ( $this->logger === null ) {
			return;
		}

		$this->logger->info( $message, $context );
	}

}
