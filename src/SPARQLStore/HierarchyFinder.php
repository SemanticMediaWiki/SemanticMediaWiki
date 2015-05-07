<?php

namespace SMW\SPARQLStore;

use Onoi\Cache\Cache;
use SMW\Store;
use SMW\DIProperty;
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

		if ( $this->cache->contains( $property->getKey() ) ) {
			return $this->cache->fetch( $property->getKey() );
		}

		$requestOptions = new RequestOptions();
		$requestOptions->limit = 1;

		$result = $this->store->getPropertySubjects(
			new DIProperty( '_SUBP' ),
			$property->getDiWikiPage(),
			$requestOptions
		);

		$this->cache->save(
			$property->getKey(),
			$result !== array()
		);

		return $result !== array();
	}

}
