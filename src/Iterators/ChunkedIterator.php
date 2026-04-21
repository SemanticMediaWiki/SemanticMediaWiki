<?php

namespace SMW\Iterators;

use ArrayIterator;
use InvalidArgumentException;
use Iterator;
use IteratorIterator;

/**
 * @see Guzzle::ChunkedIterator
 *
 * @license GPL-2.0-or-later
 * @since 3.0
 */
class ChunkedIterator extends IteratorIterator {

	private int $chunkSize = 0;

	private ?array $chunk = null;

	/**
	 * @since 3.0
	 */
	public function __construct( ResultIterator|Iterator|array $iterable, int $chunkSize = 500 ) {
		if ( is_array( $iterable ) ) {
			$iterable = new ArrayIterator( $iterable );
		}

		if ( $chunkSize < 0 ) {
			throw new InvalidArgumentException( "$chunkSize is lower than 0" );
		}

		parent::__construct( $iterable );
		$this->chunkSize = $chunkSize;
	}

	/**
	 * @since 3.0
	 *
	 * {@inheritDoc}
	 */
	public function rewind(): void {
		parent::rewind();
		$this->next();
	}

	/**
	 * @since 3.0
	 *
	 * {@inheritDoc}
	 */
	public function next(): void {
		$this->chunk = [];

		for ( $i = 0; $i < $this->chunkSize && parent::valid(); $i++ ) {
			$this->chunk[] = parent::current();
			parent::next();
		}
	}

	/**
	 * @since 3.0
	 *
	 * {@inheritDoc}
	 */
	public function current(): ?array {
		return $this->chunk;
	}

	/**
	 * @since 3.0
	 *
	 * {@inheritDoc}
	 */
	public function valid(): bool {
		return (bool)$this->chunk;
	}

}
