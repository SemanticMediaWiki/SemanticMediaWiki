<?php

namespace SMW\Schema\Filters;

use SMW\Schema\SchemaList;
use SMW\Schema\SchemaFilter;
use SMW\Schema\CompartmentIterator;
use SMW\Schema\Compartment;

/**
 * @private
 *
 * @license GNU GPL v2+
 * @since 3.2
 *
 * @author mwjames
 */
trait FilterTrait {

	/**
	 * @var iterable
	 */
	private $matches = [];

	/**
	 * @var SchemaFilter
	 */
	private $nodeFilter;

	/**
	 * @since 3.2
	 *
	 * {@inheritDoc}
	 */
	public function hasMatches() : bool {
		return $this->matches !== [];
	}

	/**
	 * @since 3.2
	 *
	 * {@inheritDoc}
	 */
	public function getMatches() : iterable {
		return $this->matches;
	}

	/**
	 * Returns information about which filter was used and how many matches where
	 * found as part of the filtering process.
	 *
	 * @since 3.2
	 */
	public function getLog() : iterable {

		$log = [
			$this->getName() => count( $this->getMatches() )
		];

		if ( $this->nodeFilter instanceof SchemaFilter ) {
			$log += $this->nodeFilter->getLog();
		}

		return $log;
	}

	/**
	 * @since 3.2
	 *
	 * {@inheritDoc}
	 */
	public function setNodeFilter( SchemaFilter $nodeFilter ) {
		$this->nodeFilter = $nodeFilter;
	}

	/**
	 * @since 3.2
	 *
	 * {@inheritDoc}
	 */
	public function filter( iterable $compartments ) {

		$this->matches = [];

		if ( $compartments instanceof CompartmentIterator ) {
			foreach ( $compartments->find( 'if' ) as $compartment ) {
				$this->match( $compartment );
			}
		} else {
			$this->match( $compartments );
		}

		if ( !$this->nodeFilter instanceof SchemaFilter ) {
			return;
		}

		$this->nodeFilter->filter(
			new CompartmentIterator( $this->matches )
		);

		$this->matches = $this->nodeFilter->getMatches();
	}

	/**
	 * @since 3.2
	 *
	 * @param Compartment $compartment
	 */
	abstract protected function match( Compartment $compartment );

}
