<?php

namespace SMW\Schema\Filters;

use SMW\Schema\SchemaList;
use SMW\Schema\SchemaFilter;
use SMW\Schema\ChainableFilter;
use SMW\Schema\CompartmentIterator;
use SMW\Schema\Compartment;
use SMW\Schema\Rule;

/**
 * @license GNU GPL v2+
 * @since 3.2
 *
 * @author mwjames
 */
class NamespaceFilter implements SchemaFilter, ChainableFilter {

	use FilterTrait;

	/**
	 * @var integer
	 */
	private $namespace;

	/**
	 * @since 3.2
	 *
	 * @param int|null $namespace
	 */
	public function __construct( ?int $namespace ) {
		$this->namespace = $namespace;
	}

	/**
	 * @since 3.2
	 *
	 * {@inheritDoc}
	 */
	public function getName() : string {
		return 'namespace';
	}

	private function match( Compartment $compartment ) {

		$namespaces = $compartment->get( 'if.namespace' );

		// In case the filter was marked as elective allows sets to remain in
		// the match pool.
		if ( $namespaces === null && $this->getOption( self::FILTER_CONDITION_NOT_REQUIRED ) === true ) {
			return $this->matches[] = $compartment;
		}

		// No restriction and no `namespace` filter was defined hence allow the
		// rule to remain in the pool of matches.
		if ( $namespaces === null && $this->namespace === null ) {
			return $this->matches[] = $compartment;
		}

		$matchedCondition = $this->matchOneOf( (array)$namespaces );

		if ( $matchedCondition === true && $compartment instanceof Rule ) {
			$compartment->incrFilterScore();
		}

		if ( $matchedCondition === true ) {
			$this->matches[] = $compartment;
		}
	}

	private function matchOneOf( array $namespaces ) {

		if ( $this->namespace === null ) {
			return false;
		}

		foreach ( $namespaces as $ns ) {

			if ( is_int( $ns ) && $ns == $this->namespace ) {
				return true;
			}

			if ( is_string( $ns ) && defined( $ns ) && ( constant( $ns ) == $this->namespace ) ) {
				return true;
			}
		}

		return false;
	}

}
