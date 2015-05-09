<?php

namespace SMW\SPARQLStore;

use Onoi\Cache\Cache;
use SMW\Store;
use SMW\DIProperty;
use SMW\DIWikiPage;
use SMWRequestOptions as RequestOptions;

/**
 * @license GNU GPL v2+
 * @since 2.3
 *
 * @author mwjames
 */
class HierarchyFinder {

	/**
	 * @var Store
	 */
	private $store = null;

	/**
	 * @var Cache|null
	 */
	private $cache = null;

	/**
	 * @since 2.3
	 *
	 * @param Store $store
	 * @param Cache|null $cache
	 */
	public function __construct( Store $store, Cache $cache ) {
		$this->store = $store;
		$this->cache = $cache;
	}

	/**
	 * @note There are different ways to find out whether a property
	 * has a subproperty or not.
	 *
	 * In SPARQL one could try using FILTER NOT EXISTS { ?s my:property ?o }
	 *
	 * @since 2.3
	 *
	 * @param DIProperty $property
	 *
	 * @return  boolean
	 */
	public function hasSubpropertyFor( DIProperty $property ) {
		return $this->hasMatchFor( '_SUBP', $property->getKey(), $property->getDiWikiPage() );
	}

	/**
	 * @since 2.3
	 *
	 * @param DIWikiPage $category
	 *
	 * @return  boolean
	 */
	public function hasSubcategoryFor( DIWikiPage $category ) {
		return $this->hasMatchFor( '_SUBC', $category->getDBKey(), $category );
	}

	private function hasMatchFor( $id, $key, DIWikiPage $subject ) {

		$key = $id . '#' . $key;

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

}
