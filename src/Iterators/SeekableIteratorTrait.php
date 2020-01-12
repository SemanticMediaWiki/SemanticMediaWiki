<?php

namespace SMW\Iterators;

use OutOfBoundsException;

/**
 * @note Traits cannot implement interfaces which means the class that uses this
 * trait is required to add `Iterator, Countable, SeekableIterator` as
 * implementation detail.
 *
 * @license GNU GPL v2+
 * @since 3.2
 *
 * @author mwjames
 */
trait SeekableIteratorTrait {

	/**
	 * @var []
	 */
	private $container = [];

	/**
	 * @var integer
	 */
	private $position = 0;

	/**
	 * @var integer
	 */
	private $count;

	/**
	 * @see Countable::count
	 * @since 3.2
	 *
	 * {@inheritDoc}
	 */
	public function count() {
		return $this->count ?? $this->count = count( $this->container );
	}

	/**
	 * @see SeekableIterator::seek
	 * @since 3.2
	 *
	 * {@inheritDoc}
	 */
	public function seek( $position ) {

		if ( !isset( $this->container[$position] ) ) {
			throw new OutOfBoundsException( "Invalid seek position ($position)" );
		}

		$this->position = $position;
	}

	/**
	 * @see Iterator::rewind
	 * @since 3.2
	 *
	 * {@inheritDoc}
	 */
	public function rewind() {
		return reset( $this->container );
	}

	/**
	 * @see Iterator::current
	 * @since 3.2
	 *
	 * {@inheritDoc}
	 */
	public function current() {

		if ( $this->position !== null ) {
			return $this->container[$this->position];
		}

		return current( $this->container );
	}

	/**
	 * @see Iterator::key
	 * @since 3.2
	 *
	 * {@inheritDoc}
	 */
	public function key() {
		return $this->position;
	}

	/**
	 * @see Iterator::next
	 * @since 3.2
	 *
	 * {@inheritDoc}
	 */
	public function next() {
		return next( $this->container );
	}

	/**
	 * @see Iterator::valid
	 * @since 3.2
	 *
	 * {@inheritDoc}
	 */
	public function valid() {
		return key( $this->container ) !== null;
	}

}
