<?php

namespace SMW\Iterators;

use Iterator;
use Countable;
use ResultWrapper;
use ArrayIterator;
use SeekableIterator;
use RuntimeException;

/**
 * @license GNU GPL v2+
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
	 * @var integer
	 */
	public $position;

	/**
	 * @var mixed
	 */
	public $current;

	/**
	 * @var boolean
	 */
	public $isResultWrapper = false;

	/**
	 * @since 2.5
	 *
	 * @param ResultWrapper|array $res
	 */
	public function __construct( $res ) {

		if ( $res instanceof ResultWrapper ) {
			$this->isResultWrapper = true;
		}

		if ( !$this->isResultWrapper && is_array( $res ) ) {
			$res = new ArrayIterator( $res );
		}

		if ( !$res instanceof Iterator && !$this->isResultWrapper ) {
			throw new RuntimeException( "ResultIterator expected an ResultWrapper or array" );
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
	public function count() {
		return $this->isResultWrapper ? $this->res->numRows() : $this->res->count();
	}

	/**
	 * @see SeekableIterator::seek
	 * @since 2.5
	 *
	 * {@inheritDoc}
	 */
	public function seek( $position ) {
		$this->res->seek( $position );
		$this->setCurrent( $this->res->current() );
		$this->position = $position;
	}

	/**
	 * @since 2.5
	 *
	 * {@inheritDoc}
	 */
	public function current() {
		return $this->current;
	}

	/**
	 * @since 2.5
	 *
	 * {@inheritDoc}
	 */
	public function key() {
		return $this->position;
	}

	/**
	 * @since 2.5
	 *
	 * {@inheritDoc}
	 */
	public function next() {
		$row = $this->res->next();
		$this->setCurrent( $row );
		$this->position++;
	}

	/**
	 * @since 2.5
	 *
	 * {@inheritDoc}
	 */
	public function rewind() {
		$this->res->rewind();
		$this->position = 0;
		$this->setCurrent( $this->res->current() );
	}

	/**
	 * @since 2.5
	 *
	 * {@inheritDoc}
	 */
	public function valid() {
		return $this->current !== false;
	}

	protected function setCurrent( $row ) {
		if ( $row === false || $row === null ) {
			$this->current = false;
		} else {
			$this->current = $row;
		}
	}

}
