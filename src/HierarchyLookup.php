<?php

namespace SMW;

use InvalidArgumentException;
use Onoi\Cache\Cache;
use Psr\Log\LoggerAwareTrait;

/**
 * @license GNU GPL v2+
 * @since 2.3
 *
 * @author mwjames
 */
class HierarchyLookup {

	use LoggerAwareTrait;

	/**
	 * Persistent cache namespace
	 */
	const CACHE_NAMESPACE = 'smw:hierarchy';

	/**
	 * Consecutive hierarchy types
	 */
	const TYPE_PROPERTY = 'type.property';
	const TYPE_CATEGORY = 'type.category';

	/**
	 * @var Store
	 */
	private $store;

	/**
	 * @var Cache|null
	 */
	private $cache;

	/**
	 * @var []
	 */
	private $inMemoryCache = [];

	/**
	 * @var integer
	 */
	private $cacheTTL;

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

		$this->cacheTTL = 60 * 60 * 24 * 7;
	}

	/**
	 * @since 3.0
	 *
	 * @param ChangePropListener $changePropListener
	 */
	public function addListenersTo( ChangePropListener $changePropListener ) {

		// @see HierarchyLookup::getConsecutiveHierarchyList
		//
		// Remove the global hierarchy cache in the event that some entity was
		// annotated (or removed) with the `Subproperty of`/ `Subcategory of`
		// property, and while this purges the entire cache we ensure that the
		// hierarchy lookup is always correct without loosing too much sleep
		// over a more fine-grained caching strategy.

		$callback = function( $context ) {
			$this->cache->delete(
				smwfCacheKey( self::CACHE_NAMESPACE, [ self::TYPE_PROPERTY, $this->subpropertyDepth ] )
			);
		};

		$changePropListener->addListenerCallback( '_SUBP', $callback );

		$callback = function( $context ) {
			$this->cache->delete(
				smwfCacheKey( self::CACHE_NAMESPACE, [ self::TYPE_CATEGORY, $this->subcategoryDepth ] )
			);
		};

		$changePropListener->addListenerCallback( '_SUBC', $callback );
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

		$result = $this->getConsecutiveHierarchyList(
			$property
		);

		return $result !== [];
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

		$result = $this->getConsecutiveHierarchyList(
			$category
		);

		return $result !== [];
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

	/**
	 * @since 3.0
	 *
	 * @param DIProperty|DIWikiPage $id
	 *
	 * @return DIProperty[]|DIWikiPage[]|[]
	 */
	public function getConsecutiveHierarchyList( $id ) {

		$hierarchyType = null;

		if ( $id instanceof DIProperty ) {
			$hierarchyType = self::TYPE_PROPERTY;
		} elseif ( $id instanceof DIWikiPage && $id->getNamespace() === NS_CATEGORY ) {
			$hierarchyType = self::TYPE_CATEGORY;
		}

		if ( $hierarchyType === null ) {
			throw new InvalidArgumentException( 'No matchable hierarchy type, expected a property or category entity.' );
		}

		// Store elements of the hierarchy tree in one large cache slot
		// since we are unable to detect if or when a leaf is removed from within
		// a cached tree unless one stores child and parent in a secondary cache.
		//
		// On the assumption that hierarchy data are less frequently changed, using
		// a "global" cache should be sufficient to avoid constant DB lookups.
		//
		// Invalidation of the cache will occur on each _SUBP/_SUBC change event (see
		// ChangePropListener).
		$cacheKey = smwfCacheKey(
			self::CACHE_NAMESPACE,
			[
				$hierarchyType,
				( $hierarchyType === self::TYPE_PROPERTY ? $this->subpropertyDepth : $this->subcategoryDepth )
			]
		);

		$hierarchyCache = $this->cache->fetch( $cacheKey );
		$reqCacheUpdate = false;

		if ( $hierarchyCache === false ) {
			$hierarchyCache = [];
		}

		$hierarchyMembers = [];
		$key = $hierarchyType === self::TYPE_PROPERTY ? $id->getKey() : $id->getDBKey();

		if ( !isset( $hierarchyCache[$key] ) ) {
			$hierarchyCache[$key] = [];

			if ( $hierarchyType === self::TYPE_PROPERTY ) {
				$this->findSubproperties( $hierarchyMembers, $id, 1 );
			} else {
				$this->findSubcategories( $hierarchyMembers, $id, 1 );
			}

			$hierarchyList[$key] = $hierarchyMembers;

			// Store only the key to keep the cache size low
			foreach ( $hierarchyList[$key] as $k ) {
				if ( $hierarchyType === self::TYPE_PROPERTY ) {
					$hierarchyCache[$key][] = $k->getKey();
				} else {
					$hierarchyCache[$key][] = $k->getDBKey();
				}
			}

			$reqCacheUpdate = true;
		} else {
			$hierarchyList[$key] = [];

			foreach ( $hierarchyCache[$key] as $k ) {
				if ( $hierarchyType === self::TYPE_PROPERTY ) {
					$hierarchyList[$key][] = new DIProperty( $k );
				} else {
					$hierarchyList[$key][] = new DIWikiPage( $k, NS_CATEGORY );
				}
			}
		}

		if ( $reqCacheUpdate ) {
			$this->cache->save( $cacheKey, $hierarchyCache, $this->cacheTTL );
		}

		return $hierarchyList[$key];
	}

	private function findSubproperties( &$hierarchyMembers, DIProperty $property, $depth ) {

		if ( $depth++ > $this->subpropertyDepth ) {
			return;
		}

		$propertyList = $this->findSubpropertyList(
			$property
		);

		if ( $propertyList === null || $propertyList === [] ) {
			return;
		}

		foreach ( $propertyList as $property ) {
			$property = DIProperty::newFromUserLabel(
				$property->getDBKey()
			);

			$hierarchyMembers[] = $property;
			$this->findSubproperties( $hierarchyMembers, $property, $depth );
		}
	}

	private function findSubcategories( &$hierarchyMembers, DIWikiPage $category, $depth ) {

		if ( $depth++ > $this->subcategoryDepth ) {
			return;
		}

		$categoryList = $this->findSubcategoryList(
			$category
		);

		foreach ( $categoryList as $category ) {
			$hierarchyMembers[] = $category;
			$this->findSubcategories( $hierarchyMembers, $category, $depth );
		}
	}

	private function lookup( $id, $key, DIWikiPage $subject, $requestOptions ) {

		$key = $id . '#' . $key . '#' . md5( $requestOptions->getHash() );

		if ( isset( $this->inMemoryCache[$key] ) ) {
			return $this->inMemoryCache[$key];
		}

		$res = $this->store->getPropertySubjects(
			new DIProperty( $id ),
			$subject,
			$requestOptions
		);

		$this->inMemoryCache[$key] = $res;

		$context = [
			'method' => __METHOD__,
			'role' => 'user',
			'id' => $id,
			'origin' => $subject
		];

		$this->logger->info( "[HierarchyLookup] Lookup: {id}, {origin}", $context );

		return $res;
	}

}
