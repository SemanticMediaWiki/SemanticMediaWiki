<?php

namespace SMW\Iterators;

use OutOfBoundsException;
use ReturnTypeWillChange;

/**
 * @note Traits cannot implement interfaces which means the class that uses this
 * trait is required to add `Iterator, Countable, SeekableIterator` as
 * implementation detail.
 *
 * @license GPL-2.0-or-later
 * @since 3.2
 *
 * @author mwjames
 */
trait SeekableIteratorTrait {

	/**
	 * @var
	 */
	private $container = [];

	/**
	 * @var int
	 */
	private $position = 0;

	/**
	 * @var int
	 */
	private $count;

	/**
	 * @see Countable::count
	 * @since 3.2
	 *
	 * {@inheritDoc}
	 */
	public function count(): int {
		if ( $this->count === null ) {
			$this->count = count( $this->container );
		}

		return $this->count;
	}

	/**
	 * @see SeekableIterator::seek
	 * @since 3.2
	 *
	 * {@inheritDoc}
	 */
	public function seek( $position ): void {
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
	public function rewind(): void {
		reset( $this->container );
	}

	/**
	 * @see Iterator::current
	 * @since 3.2
	 *
	 * {@inheritDoc}
	 */
	#[ReturnTypeWillChange]
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
	public function key(): int|string {
		return $this->position;
	}

	/**
	 * @see Iterator::next
	 * @since 3.2
	 *
	 * {@inheritDoc}
	 */
	public function next(): void {
		next( $this->container );
	}

	/**
	 * @see Iterator::valid
	 * @since 3.2
	 *
	 * {@inheritDoc}
	 */
	public function valid(): bool {
		return key( $this->container ) !== null;
	}

}
