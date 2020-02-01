<?php

namespace SMW\Schema\Filters;

use SMW\Schema\SchemaList;
use SMW\Schema\SchemaFilter;
use SMW\Schema\ChainableFilter;
use SMW\Schema\CompartmentIterator;
use SMW\Schema\Compartment;

/**
 * @license GNU GPL v2+
 * @since 3.2
 *
 * @author mwjames
 */
class CompositeFilter implements SchemaFilter {

	/**
	 * Describes the type for sorting by the filter score property
	 */
	const SORT_FILTER_SCORE = 'sort/filterscore';

	/**
	 * @var SchemaFilter[]
	 */
	private $filters = [];

	/**
	 * @var iterable
	 */
	private $matches = [];

	/**
	 * @var []
	 */
	private $options = [];

	/**
	 * @since 3.2
	 *
	 * @param SchemaFilter[] $filters
	 */
	public function __construct( array $filters ) {
		$this->filters = $filters;
	}

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
	 * @since 3.2
	 *
	 * {@inheritDoc}
	 */
	public function addOption( string $key, $value ) {
		$this->options[$key] = $value;
	}

	/**
	 * @since 3.2
	 *
	 * {@inheritDoc}
	 */
	public function sortMatches( $type, $order = 'desc' ) {

		if ( $this->matches === [] ) {
			return;
		}

		$order = strtolower( $order );

		if ( $type === self:: SORT_FILTER_SCORE ) {
			usort( $this->matches, function( $a, $b ) use ( $order ) {

				if ( $order === 'desc' ) {
					return $a->filterScore < $b->filterScore;
				}

				return $a->filterScore > $b->filterScore;
			} );
		}
	}

	/**
	 * @since 3.2
	 *
	 * {@inheritDoc}
	 */
	public function filter( iterable $compartments ) {

		$nodeFilter = null;

		foreach ( $this->filters as $filter ) {

			if ( $nodeFilter instanceof ChainableFilter ) {
				$filter->setNodeFilter( $nodeFilter );
			}

			$nodeFilter = $filter;
		}

		$filter->filter( $compartments );
		$this->matches = $filter->getMatches();
	}

}
