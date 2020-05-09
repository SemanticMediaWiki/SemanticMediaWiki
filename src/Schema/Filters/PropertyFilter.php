<?php

namespace SMW\Schema\Filters;

use SMW\Schema\SchemaList;
use SMW\Schema\SchemaFilter;
use SMW\Schema\ChainableFilter;
use SMW\Schema\CompartmentIterator;
use SMW\Schema\Compartment;
use SMW\Schema\Rule;
use SMW\DIProperty;
use RuntimeException;

/**
 * @license GNU GPL v2+
 * @since 3.2
 *
 * @author mwjames
 */
class PropertyFilter implements SchemaFilter, ChainableFilter {

	use FilterTrait;

	/**
	 * @var []
	 */
	private $properties = [];

	/**
	 * @var bool
	 */
	private $isLoaded = false;

	/**
	 * @since 3.2
	 *
	 * @param string|array|callable $properties
	 */
	public function __construct( $properties = '' ) {
		$this->properties = $properties;
	}

	/**
	 * @since 3.2
	 *
	 * {@inheritDoc}
	 */
	public function getName() : string {
		return 'property';
	}

	private function match( Compartment $compartment ) {

		if ( $this->isLoaded === false ) {
			$this->loadProperties();
		}

		$conditions = $compartment->get( 'if.property' );

		// In case the filter was marked as elective, allow sets to remain in
		// the match pool.
		if ( $conditions === null && $this->getOption( self::FILTER_CONDITION_NOT_REQUIRED ) === true ) {
			return $this->matches[] = $compartment;
		}

		// No condition to test means it is allowed to remain in the pool
		// of matches
		if ( $this->properties === [] && $conditions === null ) {
			return $this->matches[] = $compartment;
		}

		$matchedCondition = false;

		if ( is_string( $conditions ) ) {
			$matchedCondition = $this->matchOneOf( (array)$conditions );
		} elseif ( isset( $conditions['oneOf'] ) ) {
			/**
			 * `oneOf` matches against only one property
			 *
			 *```
			 * {
			 *	"if": {
			 *		"property": { "oneOf": [ "Foo", "Bar" ] }
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
			 * `anyOf` matches against any (one or more) property
			 *
			 *```
			 * {
			 *	"if": {
			 *		"property": { "anyOf": [ "Foo", "Bar" ] }
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
			 * `allOf` matches against all properties
			 *
			 *```
			 * {
			 *	"if": {
			 *		"property": { "allOf": [ "Foo", "Bar" ] }
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
			 * `not` on multiple properties means if "any of" them is validated then
			 * the condition is fullfilled.
			 *
			 *```
			 * {
			 *	"if": {
			 *		"property": { "not": [ "Foo", "Bar" ] }
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
			 *		"property": { "not": [ "Foobar" ], "oneOf": [ "Foo", "Bar" ] }
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

	private function loadProperties() {

		// Allow properties to be lazy loaded when for example those are
		// fetched from the DB
		if ( is_callable( $this->properties ) ) {
			$this->properties = ( $this->properties )();
		}

		if ( is_array( $this->properties ) || is_string( $this->properties ) ) {
			$this->properties = str_replace( ' ', '_', (array)$this->properties );
		} else {
			throw new RuntimeException(
				"Requires a string, array, or callable for the `properties` parameter!"
			);
		}

		foreach ( $this->properties as $key => $property ) {

			if ( $property === '' || $property instanceof DIProperty ) {
				continue;
			}

			$this->properties[$key] = DIProperty::newFromUserLabel( $property );
		}

		$this->isLoaded = true;
	}

	private function matchOneOf( array $properties ) : bool {

		$count = 0;

		foreach ( $properties as $prop ) {
			$prop = DIProperty::newFromUserLabel( $prop );

			foreach ( $this->properties as $property ) {
				if ( $property->equals( $prop ) ) {
					$count++;
				}
			}
		}

		return $count == 1;
	}

	private function matchAllOf( array $properties ) : bool {

		$count = count( $properties );

		foreach ( $properties as $prop ) {
			$prop = DIProperty::newFromUserLabel( $prop );

			foreach ( $this->properties as $property ) {
				if ( $property->equals( $prop ) ) {
					$count--;
				}
			}
		}

		return $count == 0;
	}

	private function matchAnyOf( array $properties ) : bool {

		foreach ( $properties as $prop ) {
			$prop = DIProperty::newFromUserLabel( $prop );

			foreach ( $this->properties as $property ) {
				if ( $property->equals( $prop ) ) {
					return true;
				}
			}
		}

		return false;
	}

}
