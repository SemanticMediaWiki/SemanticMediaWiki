<?php

namespace SMW\Iterators;

use ArrayIterator;
use InvalidArgumentException;
use IteratorIterator;
use RuntimeException;
use Traversable;

/**
 * @see Guzzle::ChunkedIterator
 *
 * @license GNU GPL v2+
 * @since 3.0
 */
class ChunkedIterator extends IteratorIterator {

	/**
	 * @var integer
	 */
	private $chunkSize = 0;

	/**
	 * @var array
	 */
	private $chunk;

	/**
	 * @since 3.0
	 *
	 * @param Traversable|array $iterator
	 * @param integer $chunkSize
	 */
	public function __construct( $iterable, $chunkSize = 500 ) {

		$chunkSize = (int)$chunkSize;

		if ( is_array( $iterable ) ) {
			$iterable = new ArrayIterator( $iterable );
		}

		if ( !$iterable instanceof Traversable ) {
			throw new RuntimeException( "ChunkedIterator expected an Traversable" );
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
   public function rewind() {
		parent::rewind();
		$this->next();
	}

	/**
	 * @since 3.0
	 *
	 * {@inheritDoc}
	 */
	public function next() {
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
	public function current() {
		return $this->chunk;
	}

	/**
	 * @since 3.0
	 *
	 * {@inheritDoc}
	 */
	public function valid() {
		return (bool) $this->chunk;
	}

}
