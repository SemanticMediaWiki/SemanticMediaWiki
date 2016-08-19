<?php

namespace SMW;

use Onoi\Cache\Cache;
use SMW\Store;

/**
 * @license GNU GPL v2+
 * @since 2.3
 *
 * @author mwjames
 */
class PropertyHierarchyLookup {

	const POOLCACHE_ID = 'property.hierarchy.lookup';

	/**
	 * @var Store
	 */
	private $store = null;

	/**
	 * @var Cache|null
	 */
	private $cache = null;

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
	public function hasSubpropertyFor( DIProperty $property ) {

		if ( $this->subpropertyDepth < 1 ) {
			return false;
		}

		$requestOptions = new RequestOptions();
		$requestOptions->limit = 1;

		$result = $this->findMatchesWith(
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
	public function hasSubcategoryFor( DIWikiPage $category ) {

		if ( $this->subcategoryDepth < 1 ) {
			return false;
		}

		$requestOptions = new RequestOptions();
		$requestOptions->limit = 1;

		$result = $this->findMatchesWith(
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
	public function findSubpropertListFor( DIProperty $property ) {
		return $this->findMatchesWith( '_SUBP', $property->getKey(), $property->getDiWikiPage(), new RequestOptions() );
	}

	/**
	 * @since 2.3
	 *
	 * @param DIWikiPage $category
	 *
	 * @return DIWikiPage[]|[]
	 */
	public function findSubcategoryListFor( DIWikiPage $category ) {
		return $this->findMatchesWith( '_SUBC', $category->getDBKey(), $category, new RequestOptions() );
	}

	private function findMatchesWith( $id, $key, DIWikiPage $subject, $requestOptions ) {

		$key = $id . '#' . $key . '#' . $requestOptions->getHash();

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

		wfDebugLog( 'smw', __METHOD__ . " {$id} and " . $subject->getDBKey() . "\n" );

		return $result;
	}

}
