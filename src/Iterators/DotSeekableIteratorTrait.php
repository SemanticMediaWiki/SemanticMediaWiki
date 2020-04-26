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
trait DotSeekableIteratorTrait {

	use SeekableIteratorTrait;

	/**
	 * @var []
	 */
	private $seekable = [];

	/**
	 * @see SeekableIterator::seek
	 * @since 3.2
	 *
	 * {@inheritDoc}
	 */
	public function seek( $position ) {

		if ( isset( $this->seekable[$position] ) ) {
			return $this->position = $position;
		}

		if ( isset( $this->container[$position] ) ) {
			return $this->position = $position;
		}

		$seekable = $this->findPosition( $position );

		if ( $seekable === null ) {
			throw new OutOfBoundsException( "Invalid seek position ($position)" );
		}

		$this->seekable[$position] = $seekable;

		return $this->position = $position;
	}

	/**
	 * @see Iterator::current
	 * @since 3.2
	 *
	 * {@inheritDoc}
	 */
	public function current() {

		if ( isset( $this->seekable[$this->position] ) ) {
			return $this->seekable[$this->position];
		}

		if ( $this->position !== null ) {
			return $this->container[$this->position];
		}

		return current( $this->container );
	}

	private function findPosition( $position ) {

		// Allow to seek using the dot notation
		if ( !is_string( $position ) || strpos( $position, '.' ) === false ) {
			return null;
		}

		$seekable = $this->container;

		foreach ( \explode( '.', $position ) as $segment ) {

			if ( !\is_array( $seekable ) || !\array_key_exists( $segment, $seekable ) ) {
				$seekable = null;
			}

			$seekable = &$seekable[$segment];
		}

		return $seekable;
	}

}
