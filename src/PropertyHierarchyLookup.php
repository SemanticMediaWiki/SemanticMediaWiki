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
	 * @return  boolean
	 */
	public function hasSubpropertyFor( DIProperty $property ) {

		if ( $this->subpropertyDepth < 1 ) {
			return false;
		}

		return $this->hasMatchFor( '_SUBP', $property->getKey(), $property->getDiWikiPage() );
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

		return $this->hasMatchFor( '_SUBC', $category->getDBKey(), $category );
	}

	/**
	 * @since 2.3
	 *
	 * @param DIProperty $property
	 *
	 * @return DIWikiPage[]|[]
	 */
	public function findSubpropertListFor( DIProperty $property ) {
		return $this->findMatchesFor( '_SUBP', $property->getKey(), $property->getDiWikiPage() );
	}

	/**
	 * @since 2.3
	 *
	 * @param DIWikiPage $category
	 *
	 * @return DIWikiPage[]|[]
	 */
	public function findSubcategoryListFor( DIWikiPage $category ) {
		return $this->findMatchesFor( '_SUBC', $category->getDBKey(), $category );
	}

	private function hasMatchFor( $id, $key, DIWikiPage $subject ) {

		$key = 'm#' . $id . '#' . $key;

		if ( $this->cache->contains( $key ) ) {
			return $this->cache->fetch( $key );
		}

		$requestOptions = new RequestOptions();
		$requestOptions->limit = 1;

		$result = $this->store->getPropertySubjects(
			new DIProperty( $id ),
			$subject,
			$requestOptions
		);

		$this->cache->save(
			$key,
			$result !== array()
		);

		return $result !== array();
	}

	private function findMatchesFor( $id, $key, DIWikiPage $subject ) {

		$key = 'f#' . $id . '#' . $key;

		if ( $this->cache->contains( $key ) ) {
			return unserialize( $this->cache->fetch( $key ) );
		}

		$requestOptions = new RequestOptions();

		$result = $this->store->getPropertySubjects(
			new DIProperty( $id ),
			$subject,
			$requestOptions
		);

		$this->cache->save(
			$key,
			serialize( $result )
		);

		wfDebugLog( 'smw', __METHOD__ . " {$id} and " . $subject->getDBKey() . "\n" );

		return $result;
	}

}
