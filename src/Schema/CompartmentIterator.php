<?php

namespace SMW\Schema;

use SeekableIterator;
use Iterator;
use Countable;
use OutOfBoundsException;

/**
 * @license GNU GPL v2+
 * @since 3.1
 *
 * @author mwjames
 */
class CompartmentIterator implements Iterator, Countable, SeekableIterator {

	/**
	 * @var []
	 */
	private $compartments = [];

	/**
	 * @var integer
	 */
	private $position = 0;

	/**
	 * @var integer
	 */
	private $count;

	/**
	 * @since 3.1
	 *
	 * @param array $compartments
	 */
	public function __construct( array $compartments = [] ) {
		$this->compartments = $compartments;
		$this->position = 0;
	}

	/**
	 * @see Countable::count
	 * @since 3.1
	 *
	 * {@inheritDoc}
	 */
	public function count() {

		if ( $this->count === null ) {
			$this->count = count( $this->compartments );
		}

		return $this->count;
	}

	/**
	 * @see SeekableIterator::seek
	 * @since 3.1
	 *
	 * {@inheritDoc}
	 */
	public function seek( $position ) {

		if ( !isset( $this->compartments[$position] ) ) {
			throw new OutOfBoundsException( "Invalid seek position ($position)" );
		}

		$this->position = $position;
	}

	/**
	 * @see Iterator::rewind
	 * @since 3.1
	 *
	 * {@inheritDoc}
	 */
	public function rewind() {
		$this->position = 0;
	}

	/**
	 * @see Iterator::current
	 * @since 3.1
	 *
	 * {@inheritDoc}
	 */
	public function current() {
		return new Compartment( $this->compartments[$this->position] );
	}

	/**
	 * @see Iterator::key
	 * @since 3.1
	 *
	 * {@inheritDoc}
	 */
	public function key() {
		return $this->position;
	}

	/**
	 * @see Iterator::next
	 * @since 3.1
	 *
	 * {@inheritDoc}
	 */
	public function next() {
		++$this->position;
	}

	/**
	 * @see Iterator::valid
	 * @since 3.1
	 *
	 * {@inheritDoc}
	 */
	public function valid() {
		return isset( $this->compartments[$this->position] );
	}

}
