<?php

namespace SMW;

use InvalidArgumentException;
use Iterator;
use Onoi\Cache\Cache;
use Psr\Log\LoggerAwareTrait;
use SMW\DataItems\Property;
use SMW\DataItems\WikiPage;
use SMW\Listener\ChangeListener\ChangeListeners\PropertyChangeListener;

/**
 * @license GPL-2.0-or-later
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
	const TYPE_PROPERTY = 'type/property';
	const TYPE_CATEGORY = 'type/category';

	/**
	 * Consecutive hierarchy direction
	 */
	const TYPE_SUPER = 'type/super';
	const TYPE_SUB = 'type/sub';

	private Store $store;

	private Cache $cache;

	private array $inMemoryCache = [];

	private int $cacheTTL = 60 * 60 * 24 * 7;

	/**
	 * Use 0 to disable the hierarchy lookup
	 */
	private int $subcategoryDepth = 10;

	/**
	 * Use 0 to disable the hierarchy lookup
	 */
	private int $subpropertyDepth = 10;

	/**
	 * @since 2.3
	 */
	public function __construct( Store $store, Cache $cache ) {
		$this->store = $store;
		$this->cache = $cache;
	}

	/**
	 * @since 3.0
	 */
	public function registerPropertyChangeListener( PropertyChangeListener $changeListener ): void {
		$changeListener->addListenerCallback( new Property( '_SUBP' ), [ $this, 'invalidateCache' ] );
		$changeListener->addListenerCallback( new Property( '_SUBC' ), [ $this, 'invalidateCache' ] );
	}

	/**
	 * Remove the global hierarchy cache in the event that some entity was annotated
	 * (or removed) with the `Subproperty of`/ `Subcategory of` property, and
	 * while this purges the entire cache we ensure that the hierarchy lookup is
	 * always correct without loosing too much sleep over a more fine-grained
	 * caching strategy.
	 *
	 * @since 3.2
	 */
	public function invalidateCache( Property $property ): void {
		if ( $property->getKey() === '_SUBP' ) {
			$this->cache->delete(
				smwfCacheKey( self::CACHE_NAMESPACE, [ self::TYPE_PROPERTY, self::TYPE_SUB, $this->subpropertyDepth ] )
			);

			$this->cache->delete(
				smwfCacheKey( self::CACHE_NAMESPACE, [ self::TYPE_PROPERTY, self::TYPE_SUPER, $this->subpropertyDepth ] )
			);
		}

		if ( $property->getKey() === '_SUBC' ) {
			$this->cache->delete(
				smwfCacheKey( self::CACHE_NAMESPACE, [ self::TYPE_CATEGORY, self::TYPE_SUB, $this->subcategoryDepth ] )
			);

			$this->cache->delete(
				smwfCacheKey( self::CACHE_NAMESPACE, [ self::TYPE_CATEGORY, self::TYPE_SUPER, $this->subpropertyDepth ] )
			);
		}
	}

	/**
	 * @since 2.3
	 */
	public function setSubcategoryDepth( mixed $subcategoryDepth ): void {
		$this->subcategoryDepth = (int)$subcategoryDepth;
	}

	/**
	 * @since 2.3
	 */
	public function setSubpropertyDepth( mixed $subpropertyDepth ): void {
		$this->subpropertyDepth = (int)$subpropertyDepth;
	}

	/**
	 * @since 2.3
	 */
	public function hasSubproperty( Property $property ): bool {
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
	 */
	public function hasSubcategory( WikiPage $category ): bool {
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
	 * @return WikiPage[]|array|Iterator|false
	 */
	public function findSubpropertyList( Property $property ): array|Iterator|false {
		if ( $this->subpropertyDepth < 1 ) {
			return false;
		}

		return $this->lookup(
			'_SUBP',
			$property->getKey(),
			$property->getDiWikiPage(),
			new RequestOptions()
		);
	}

	/**
	 * @since 2.3
	 */
	public function findSubcategoryList( WikiPage $category ): array|Iterator {
		if ( $this->subcategoryDepth < 1 ) {
			return [];
		}

		return $this->lookup( '_SUBC', $category->getDBKey(), $category, new RequestOptions() );
	}

	/**
	 * @since 3.1
	 *
	 * @return WikiPage[]|Iterator
	 */
	public function findNearbySuperCategories( WikiPage $category ): array|Iterator {
		if ( $this->subcategoryDepth < 1 ) {
			return [];
		}

		return $this->lookup(
			new Property( '_SUBC', true ),
			$category->getDBKey(),
			$category,
			new RequestOptions()
		);
	}

	/**
	 * @since 3.0
	 *
	 * @return Property[]|WikiPage[]
	 */
	public function getConsecutiveHierarchyList(
		Property|WikiPage $id,
		string $hierarchyType = self::TYPE_SUB
	): array {
		$objectType = null;

		if ( $id instanceof Property ) {
			$objectType = self::TYPE_PROPERTY;
		} elseif ( $id->getNamespace() === NS_CATEGORY ) {
			$objectType = self::TYPE_CATEGORY;
		}

		if ( $objectType === null ) {
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
		// PropertyChangeListener).
		$depth = $objectType === self::TYPE_PROPERTY ? $this->subpropertyDepth : $this->subcategoryDepth;

		$cacheKey = smwfCacheKey(
			self::CACHE_NAMESPACE,
			[
				$objectType,
				$hierarchyType,
				$depth
			]
		);

		$reqCacheUpdate = false;
		$hierarchyMembers = [];

		$hierarchyCache = $this->cache->fetch( $cacheKey );
		if ( !$hierarchyCache ) {
			$hierarchyCache = [];
		}

		$key = $objectType === self::TYPE_PROPERTY ? $id->getKey() : $id->getDBKey();

		if ( !isset( $hierarchyCache[$key] ) ) {
			$hierarchyCache[$key] = [];

			if ( $objectType === self::TYPE_PROPERTY ) {
				$this->findSubproperties( $hierarchyMembers, $id, 1 );
			} else {
				if ( $hierarchyType === self::TYPE_SUPER ) {
					$this->findSuperCategoriesByDepth( $hierarchyMembers, $id, 1 );
				} else {
					$this->findSubcategories( $hierarchyMembers, $id, 1 );
				}
			}

			$hierarchyList[$key] = $hierarchyMembers;

			// Store only the key to keep the cache size low
			foreach ( $hierarchyList[$key] as $k ) {
				if ( $objectType === self::TYPE_PROPERTY ) {
					$hierarchyCache[$key][] = $k->getKey();
				} else {
					$hierarchyCache[$key][] = $k->getDBKey();
				}
			}

			$reqCacheUpdate = true;
		} else {
			$hierarchyList[$key] = [];

			foreach ( $hierarchyCache[$key] as $k ) {
				if ( $objectType === self::TYPE_PROPERTY ) {
					$hierarchyList[$key][] = new Property( $k );
				} else {
					$hierarchyList[$key][] = new WikiPage( $k, NS_CATEGORY );
				}
			}
		}

		if ( $reqCacheUpdate ) {
			$this->cache->save( $cacheKey, $hierarchyCache, $this->cacheTTL );
		}

		return $hierarchyList[$key];
	}

	private function findSubproperties( &$hierarchyMembers, Property $property, int|float $depth ): void {
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
			$property = Property::newFromUserLabel(
				$property->getDBKey()
			);

			$hierarchyMembers[] = $property;
			$this->findSubproperties( $hierarchyMembers, $property, $depth );
		}
	}

	private function findSubcategories( &$hierarchyMembers, WikiPage $category, int|float $depth ): void {
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

	private function findSuperCategoriesByDepth( &$hierarchyMembers, WikiPage $category, int|float $depth ): void {
		if ( $depth++ > $this->subcategoryDepth ) {
			return;
		}

		$categoryList = $this->findNearbySuperCategories(
			$category
		);

		foreach ( $categoryList as $category ) {
			$hierarchyMembers[] = $category;
			$this->findSuperCategoriesByDepth( $hierarchyMembers, $category, $depth );
		}
	}

	private function lookup( string|Property $property, $key, WikiPage $subject, RequestOptions $requestOptions ): array|Iterator {
		if ( is_string( $property ) ) {
			$property = new Property( $property );
		}

		$key = md5(
			$property->getKey() . '#' .
			(string)$property->isInverse() . '#' .
			$key . '#' .
			$requestOptions->getHash()
		);

		if ( isset( $this->inMemoryCache[$key] ) ) {
			return $this->inMemoryCache[$key];
		}

		$requestOptions->setCaller( __METHOD__ );

		$subjects = $this->store->getPropertySubjects(
			$property,
			$subject,
			$requestOptions
		);

		$this->inMemoryCache[$key] = $subjects;

		$this->logger->info(
			[ 'HierarchyLookup', "Lookup for: {id}, {origin}" ],
			[ 'method' => __METHOD__, 'role' => 'user', 'id' => $property->getKey(), 'origin' => $subject ]
		);

		return $subjects;
	}

}
