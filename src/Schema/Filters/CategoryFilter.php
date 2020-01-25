<?php

namespace SMW\Schema\Filters;

use SMW\Schema\SchemaList;
use SMW\Schema\SchemaFilter;
use SMW\Schema\CompartmentIterator;
use SMW\Schema\Compartment;
use RuntimeException;

/**
 * @license GNU GPL v2+
 * @since 3.2
 *
 * @author mwjames
 */
class CategoryFilter implements SchemaFilter {

	use FilterTrait;

	/**
	 * @var []
	 */
	private $categories = [];

	/**
	 * @since 3.2
	 *
	 * @param string|array|callable $categories
	 */
	public function __construct( $categories = '' ) {

		if ( is_array( $categories ) || is_string( $categories ) ) {
			$this->categories = array_flip( str_replace( ' ', '_', (array)$categories ) );
		} elseif ( is_callable( $categories ) ) {
			// Allow categories to be lazy loaded when for example those are
			// fetched from the DB
			$this->categories = $categories;
		} else {
			throw new RuntimeException( "Requires a string, array, or callable as constructor argument!" );
		}
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

		if ( is_callable( $this->categories ) ) {
			$this->categories = array_flip( str_replace( ' ', '_', ( $this->categories )() ) );
		}

		$conditions = $compartment->get( 'if.category' );

		// No condition to test means it is allowed to remain in the pool
		// of matches
		if ( $this->categories === [] && $conditions === null ) {
			return $this->matches[] = $compartment;
		}

		$matchedCondition = false;

		if ( is_string( $conditions ) || (is_array( $conditions ) && isset( $conditions[0] ) ) ) {
			$matchedCondition = $this->matchOneOf( (array)$conditions );
		} elseif ( isset( $conditions['oneOf'] ) ) {
			 /**
			 * `oneOf` matches against only one category
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
		}

		if ( $matchedCondition === true ) {
			$this->matches[] = $compartment;
		}
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
