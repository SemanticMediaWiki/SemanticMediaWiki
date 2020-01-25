<?php

namespace SMW\Schema;

use SeekableIterator;
use Iterator;
use Countable;
use OutOfBoundsException;
use RuntimeException;
use SMW\Iterators\SeekableIteratorTrait;

/**
 * @license GNU GPL v2+
 * @since 3.1
 *
 * @author mwjames
 */
class CompartmentIterator implements Iterator, Countable, SeekableIterator {

	use SeekableIteratorTrait;

	/**
	 * @since 3.1
	 *
	 * @param array $compartments
	 */
	public function __construct( array $compartments = [] ) {
		$this->container = $compartments;
	}

	/**
	 * @see Iterator::current
	 * @since 3.1
	 *
	 * {@inheritDoc}
	 */
	public function current() {

		$data = current( $this->container );

		if ( $data instanceof Compartment ) {
			return $data;
		}

		if ( !is_array( $data ) ) {
			$data = [ $this->position => $data ];
		}

		return new Compartment( $data );
	}

	/**
	 * @since 3.2
	 *
	 * @param string $key
	 *
	 * @return CompartmentIterator
	 */
	public function find( string $key ) : CompartmentIterator {

		$meta = [];
		$result = [];

		return new CompartmentIterator(
			$this->search( $key, $this->container, $meta, $result )
		);
	}

	private function search( $key, $data, $meta, &$result ) {

		foreach ( $data as $section => $value ) {

			if ( $value instanceof Compartment && $value->has( $key ) ) {
				$result[] = $value;
			} elseif ( is_array( $value ) ) {

				if ( isset( $data[Compartment::ASSOCIATED_SCHEMA] ) ) {
					$meta[Compartment::ASSOCIATED_SCHEMA] = $data[Compartment::ASSOCIATED_SCHEMA];
				}

				$meta[Compartment::ASSOCIATED_SECTION] = $section;

				if ( isset( $value[$key] ) ) {
					$result[] = $value + $meta;
				}

				$this->search( $key, $value, $meta, $result );
			}
		}

		return $result;
	}

}
