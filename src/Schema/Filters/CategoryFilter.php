<?php

namespace SMW\Schema\Filters;

use SMW\Schema\SchemaList;
use SMW\Schema\SchemaFilter;
use SMW\Schema\ChainableFilter;
use SMW\Schema\CompartmentIterator;
use SMW\Schema\Compartment;
use SMW\Schema\Rule;
use RuntimeException;

/**
 * @license GNU GPL v2+
 * @since 3.2
 *
 * @author mwjames
 */
class CategoryFilter implements SchemaFilter, ChainableFilter {

	use FilterTrait;

	/**
	 * @var []
	 */
	private $categories = [];

	/**
	 * @var bool
	 */
	private $isLoaded = false;

	/**
	 * @since 3.2
	 *
	 * @param string|array|callable $categories
	 */
	public function __construct( $categories = '' ) {
		$this->categories = $categories;
	}

	/**
	 * @since 3.2
	 *
	 * {@inheritDoc}
	 */
	public function getName() : string {
		return 'category';
	}

	private function match( Compartment $compartment ) {

		if ( $this->isLoaded === false ) {
			$this->loadCategories();
		}

		$conditions = $compartment->get( 'if.category' );

		// In case the filter was marked as elective, allow sets to remain in
		// the match pool.
		if ( $conditions === null && $this->getOption( self::FILTER_CONDITION_NOT_REQUIRED ) === true ) {
			return $this->matches[] = $compartment;
		}

		// No restriction and no `category` filter was defined hence allow the
		// rule to remain in the pool of matches.
		if ( $this->categories === [] && $conditions === null ) {
			return $this->matches[] = $compartment;
		}

		$matchedCondition = false;

		if ( is_string( $conditions ) || ( is_array( $conditions ) && isset( $conditions[0] ) ) ) {
			$matchedCondition = $this->matchOneOf( (array)$conditions );
		} elseif ( isset( $conditions['oneOf'] ) ) {
			/**
			 * `oneOf` matches against only one category
			 *
			 *```
			 * {
			 *	"if": {
			 *		"category": { "oneOf": [ "Foo", "Bar" ] }
			 *	},
			 *	"then": {
			 *		...
			 *	}
			 *}
			 *```
			 */
			$matchedCondition = $this->matchOneOf( (array)$conditions['oneOf'] );
		} elseif ( isset( $conditions['anyOf'] ) ) {
			/**
			 * `anyOf` matches against any (one or more) category
			 *
			 *```
			 * {
			 *	"if": {
			 *		"category": { "anyOf": [ "Foo", "Bar" ] }
			 *	},
			 *	"then": {
			 *		...
			 *	}
			 *}
			 *```
			 */
			$matchedCondition = $this->matchAnyOf( (array)$conditions['anyOf'] );
		} elseif ( isset( $conditions['allOf'] ) ) {
			/**
			 * `allOf` matches against all categories
			 *
			 *```
			 * {
			 *	"if": {
			 *		"category": { "allOf": [ "Foo", "Bar" ] }
			 *	},
			 *	"then": {
			 *		...
			 *	}
			 *}
			 *```
			 */
			$matchedCondition = $this->matchAllOf( (array)$conditions['allOf'] );
		} elseif ( isset( $conditions['not'] ) ) {
			/**
			 * `not` on multiple categories means if "any of" them is validated then
			 * the condition is fullfilled.
			 *
			 *```
			 * {
			 *	"if": {
			 *		"category": { "not": [ "Foo", "Bar" ] }
			 *	},
			 *	"then": {
			 *		...
			 *	}
			 *}
			 *```
			 */
			$matchedCondition = !$this->matchAnyOf( (array)$conditions['not'] );
			unset( $conditions['not'] );
		}

		if ( $matchedCondition === true && $compartment instanceof Rule ) {
			$compartment->incrFilterScore();
		}

		if ( $matchedCondition && isset( $conditions['not'] ) ) {
			/**
			 *```
			 * {
			 *	"if": {
			 *		"category": { "not": [ "Foobar" ], "oneOf": [ "Foo", "Bar" ] }
			 *	},
			 *	"then": {
			 *		...
			 *	}
			 *}
			 *```
			 */
			$matchedCondition = !$this->matchAnyOf( (array)$conditions['not'] );

			// Increasing the score in case an extra `not` condition was applied
			if ( $matchedCondition === true && $compartment instanceof Rule ) {
				$compartment->incrFilterScore();
			}
		}

		if ( $matchedCondition === true ) {
			$this->matches[] = $compartment;
		}
	}

	private function loadCategories() {

		// Allow categories to be lazy loaded when for example those are
		// fetched from the DB
		if ( is_callable( $this->categories ) ) {
			$this->categories = ( $this->categories )();
		}

		if ( is_array( $this->categories ) || is_string( $this->categories ) ) {
			$this->categories = str_replace( ' ', '_', (array)$this->categories );
		} else {
			throw new RuntimeException(
				"Requires a string, array, or callable for the `categories` parameter!"
			);
		}

		// Always ensure we have an associative array for an index access
		if (
			\array_key_exists( 0, $this->categories ) &&
			array_keys( $this->categories ) === range( 0, count( $this->categories ) - 1 ) ) {
			$this->categories = array_flip( $this->categories );
		}

		$this->isLoaded = true;
	}

	private function matchAllOf( array $categories ) : bool {

		$count = count( $categories );

		foreach ( $categories as $category ) {
			$category = str_replace( ' ', '_', $category );

			if ( isset( $this->categories[$category] ) ) {
				$count--;
			}
		}

		return $count == 0;
	}

	private function matchOneOf( array $categories ) : bool {

		$count = 0;

		foreach ( $categories as $category ) {
			$category = str_replace( ' ', '_', $category );

			if ( isset( $this->categories[$category] ) ) {
				$count++;
			}
		}

		return $count == 1;
	}

	private function matchAnyOf( array $categories ) : bool {

		foreach ( $categories as $category ) {
			$category = str_replace( ' ', '_', $category );

			if ( isset( $this->categories[$category] ) ) {
				return true;
			}
		}

		return false;
	}

}
