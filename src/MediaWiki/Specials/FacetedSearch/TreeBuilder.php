<?php

namespace SMW\MediaWiki\Specials\FacetedSearch;

use SMW\DIProperty;
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

	/**
	 * @var Store
	 */
	private $store;

	/**
	 * @var
	 */
	private $nodes;

	/**
	 * @since 3.2
	 *
	 * @param Store $store
	 */
	public function __construct( Store $store ) {
		$this->store = $store;
	}

	/**
	 * @since 3.2
	 *
	 * @param Node $node
	 */
	public function addNode( $node ) {
		$this->nodes[$node->id] = $node;
	}

	/**
	 * @since 3.2
	 *
	 * @param array $items
	 */
	public function setNodes( array $items ) {
		$this->nodes = [];

		foreach ( $items as $id => $item ) {
			$this->addNode( $this->newNode( $id, $item ) );
		}
	}

	/**
	 * @since 3.2
	 *
	 * @param array $subjects
	 * @param string $type
	 *
	 * @return
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
			new DIProperty( $property ),
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
	public function buildFrom( array $subjects, string $type ) {
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
	 *
	 * @param $node
	 */
	public function removeNode( $node ) {
		unset( $this->nodes[$node->id] );
	}

	public function hasNode( $id ) {
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

	public function getTree() {
		$text = '';

		if ( $this->nodes === [] || $this->nodes === null ) {
			return $text;
		}

		foreach ( $this->nodes as $node ) {
			$text .= $node->getString();
		}

		return "<ul>$text</ul>";
	}

	public function newNode( $id, $content = '' ) {
		return new class ( $id, $content ) {

			public $id;
			public $content = '';
			public $children = [];

			public function __construct( $id, $content ) {
				$this->id = $id;
				$this->content = $content;
			}

			public function hasNode( $id ) {
				if ( isset( $this->children[$id] ) ) {
					return $this->children[$id];
				}

				foreach ( $this->children as $key => $child ) {
					if ( $child->hasNode( $id ) ) {
						return true;
					}
				}

				return false;
			}

			public function getNode( $id ) {
				if ( isset( $this->children[$id] ) ) {
					return $this->children[$id];
				}

				foreach ( $this->children as $key => $child ) {
					if ( $child->hasNode( $id ) ) {
						return $child->getNode( $id );
					}
				}
			}

			public function addChild( $node ) {
				$this->children[$node->id] = $node;
			}

			public function getString() {
				if ( is_array( $this->content ) ) {
					$text = implode( '', $this->content );
				} else {
					$text = $this->content;
				}

				if ( $text === '' ) {
					$text = '<li><div class="blank-item">' . $this->id . '</div>';
				}

				// Remove the last </li> from the current <li> element to ensure
				// the <ul> becomes part of the <li> element otherwise the elements
				// aren't correct positioned as per HTML standard.
				if ( $this->children !== [] && substr( "$text", -5 ) === '</li>' ) {
					$text = substr_replace( $text, "", -5 );
				}

				if ( $this->children !== [] ) {
					$text .= '<ul class="child">';
				}

				foreach ( $this->children as $child ) {
					$text .= $child->getString();
				}

				if ( $this->children !== [] ) {
					$text .= '</ul></li>';
				}

				return $text;
			}
		};
	}

}
