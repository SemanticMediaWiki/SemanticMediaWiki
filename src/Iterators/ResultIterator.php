<?php

namespace SMW\Iterators;

use ArrayIterator;
use Countable;
use Iterator;
use RuntimeException;
use SeekableIterator;
use Wikimedia\Rdbms\ResultWrapper;

/**
 * @license GPL-2.0-or-later
 * @since 2.5
 *
 * @author mwjames
 */
class ResultIterator implements Iterator, Countable, SeekableIterator {

	/**
	 * @var ResultWrapper
	 */
	public $res;

	/**
	 * @var int
	 */
	public $position;

	/**
	 * @var mixed
	 */
	public $current;

	/**
	 * @var bool
	 */
	public $numRows = false;

	/**
	 * @since 2.5
	 *
	 * @param Iterator|array $res
	 */
	public function __construct( $res ) {
		if ( !$res instanceof Iterator && !is_array( $res ) ) {
			throw new RuntimeException( "Expected an Iterator or array!" );
		}

		// @see MediaWiki's ResultWrapper
		if ( $res instanceof Iterator && method_exists( $res, 'numRows' ) ) {
			$this->numRows = true;
		}

		if ( is_array( $res ) ) {
			$res = new ArrayIterator( $res );
		}

		$this->res = $res;
		$this->position = 0;
		$this->setCurrent( $this->res->current() );
	}

	/**
	 * @see Countable::count
	 * @since 2.5
	 *
	 * {@inheritDoc}
	 */
	public function count(): int {
		return $this->numRows ? $this->res->numRows() : $this->res->count();
	}

	/**
	 * @see SeekableIterator::seek
	 * @since 2.5
	 *
	 * {@inheritDoc}
	 */
	public function seek( $position ): void {
		$this->res->seek( $position );
		$this->setCurrent( $this->res->current() );
		$this->position = $position;
	}

	/**
	 * @since 2.5
	 *
	 * {@inheritDoc}
	 */
	#[\ReturnTypeWillChange]
	public function current() {
		return $this->current;
	}

	/**
	 * @since 2.5
	 *
	 * {@inheritDoc}
	 */
	#[\ReturnTypeWillChange]
	public function key() {
		return $this->position;
	}

	/**
	 * @since 2.5
	 *
	 * {@inheritDoc}
	 */
	public function next(): void {
		$this->res->next();
		$this->setCurrent( $this->res->current() );
		$this->position++;
	}

	/**
	 * @since 2.5
	 *
	 * {@inheritDoc}
	 */
	public function rewind(): void {
		$this->res->rewind();
		$this->position = 0;
		$this->setCurrent( $this->res->current() );
	}

	/**
	 * @since 2.5
	 *
	 * {@inheritDoc}
	 */
	public function valid(): bool {
		return $this->current !== false && $this->position < $this->count();
	}

	protected function setCurrent( $row ) {
		if ( $row === false || $row === null ) {
			$this->current = false;
		} else {
			$this->current = $row;
		}
	}

}
