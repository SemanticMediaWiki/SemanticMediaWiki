<?php

namespace SMW\MediaWiki\Specials\FacetedSearch;

use SMW\DataItems\Property;
use SMW\RequestOptions;
use SMW\SQLStore\EntityStore\PrefetchItemLookup;
use SMW\Store;

/**
 * @license GPL-2.0-or-later
 * @since   3.2
 *
 * @author mwjames
 */
class TreeBuilder {

	/**
	 * Building a tree for properties
	 */
	const TYPE_PROPERTY = 'type/property';

	/**
	 * Building a tree for categories
	 */
	const TYPE_CATEGORY = 'type/category';

	private ?array $nodes = null;

	/**
	 * @since 3.2
	 */
	public function __construct( private readonly Store $store ) {
	}

	/**
	 * @since 3.2
	 */
	public function addNode( Node $node ): void {
		$this->nodes[$node->id] = $node;
	}

	/**
	 * @since 3.2
	 */
	public function setNodes( array $items ): void {
		$this->nodes = [];

		foreach ( $items as $id => $item ) {
			$this->addNode( $this->newNode( $id, $item ) );
		}
	}

	/**
	 * @since 3.2
	 */
	public function getHierarchyList( array $subjects, string $type ): array {
		if ( $subjects === [] ) {
			return [];
		}

		$this->store->getObjectIds()->warmUpCache(
			$subjects
		);

		$prefetchItemLookup = $this->store->service( 'PrefetchItemLookup' );

		$requestOptions = new RequestOptions();
		$requestOptions->setOption( PrefetchItemLookup::HASH_INDEX, true );

		$property = $type === self::TYPE_CATEGORY ? '_SUBC' : '_SUBP';

		$propertyValues = $prefetchItemLookup->getPropertyValues(
			$subjects,
			new Property( $property ),
			$requestOptions
		);

		$hierarchyList = [];

		foreach ( $subjects as $subject ) {
			$hash = $subject->getHash();
			$label = str_replace( '_', ' ', $subject->getDBKey() );

			if ( isset( $propertyValues[$hash] ) ) {
				$parents = [];

				foreach ( $propertyValues[$hash] as $parent ) {
					$parents[] = str_replace( '_', ' ', $parent->getDBKey() );
				}

				$hierarchyList[$label] = $parents;
			} else {
				$hierarchyList[$label] = [];
			}
		}

		return $hierarchyList;
	}

	/**
	 * @since 3.2
	 *
	 * @param array $subjects
	 * @param string $type
	 */
	public function buildFrom( array $subjects, string $type ): void {
		$hierarchyList = $this->getHierarchyList(
			$subjects,
			$type
		);

		foreach ( $hierarchyList as $id => $parents ) {

			if ( !$this->hasNode( $id ) ) {
				continue;
			}

			$node = $this->getNode( $id );

			foreach ( $parents as $parent ) {

				if ( !$this->hasNode( $parent ) ) {
					$this->addNode( $this->newNode( $parent ) );
				}

				$parentNode = $this->getNode( $parent );
				$parentNode->addChild( $node );
				$this->removeNode( $node );
			}
		}
	}

	/**
	 * @since 3.2
	 */
	public function removeNode( Node $node ): void {
		unset( $this->nodes[$node->id] );
	}

	public function hasNode( $id ): bool {
		if ( $this->nodes === [] || $this->nodes === null ) {
			return false;
		}

		if ( isset( $this->nodes[$id] ) ) {
			return true;
		}

		foreach ( $this->nodes as $key => $node ) {
			if ( $node->hasNode( $id ) ) {
				return true;
			}
		}

		return false;
	}

	public function getNode( $id ) {
		if ( isset( $this->nodes[$id] ) ) {
			return $this->nodes[$id];
		}

		foreach ( $this->nodes as $key => $node ) {
			if ( $node->hasNode( $id ) ) {
				return $node->getNode( $id );
			}
		}
	}

	public function getTree(): string {
		$text = '';

		if ( $this->nodes === [] || $this->nodes === null ) {
			return $text;
		}

		foreach ( $this->nodes as $node ) {
			$text .= $node->getString();
		}

		return "<ul>$text</ul>";
	}

	public function newNode( string $id, string|array $content = '' ): Node {
		return new Node( $id, $content );
	}

}
