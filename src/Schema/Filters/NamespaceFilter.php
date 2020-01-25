<?php

namespace SMW\Schema\Filters;

use SMW\Schema\SchemaList;
use SMW\Schema\SchemaFilter;
use SMW\Schema\CompartmentIterator;
use SMW\Schema\Compartment;

/**
 * @license GNU GPL v2+
 * @since 3.2
 *
 * @author mwjames
 */
class NamespaceFilter implements SchemaFilter {

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

		if ( $namespaces === null && $this->namespace === null ) {
			return $this->matches[] = $compartment;
		}

		if ( $this->matchOneOf( (array)$namespaces ) ) {
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
