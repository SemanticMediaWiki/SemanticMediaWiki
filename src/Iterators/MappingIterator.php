<?php

namespace SMW\Iterators;

use ArrayIterator;
use Countable;
use Iterator;
use IteratorIterator;
use RuntimeException;

/**
 * This iterator is expected to be called in combination with another iterator
 * (or traversable/array) in order to apply a mapping on the returned current element
 * during an iterative (foreach etc.) process.
 *
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class MappingIterator extends IteratorIterator implements Countable {

	/**
	 * @var callable
	 */
	private $callback;

	/**
	 * @var integer
	 */
	private $count = 1;

	/**
	 * @since 2.5
	 *
	 * @param Iterator|array $iterable
	 * @param callable  $callback
	 */
	public function __construct( $iterable, callable $callback ) {

		if ( is_array( $iterable ) ) {
			$iterable = new ArrayIterator( $iterable );
		}

		if ( !$iterable instanceof Iterator ) {
			throw new RuntimeException( "MappingIterator expected an Iterator" );
		}

		if ( $iterable instanceof Countable ) {
			$this->count = $iterable->count();
		}

		parent::__construct( $iterable );
		$this->callback = $callback;
	}

	/**
	 * @see Countable::count
	 * @since 2.5
	 *
	 * {@inheritDoc}
	 */
	public function count() {
		return $this->count;
	}

	/**
	 * @since 2.5
	 *
	 * {@inheritDoc}
	 */
	public function current() {
		return call_user_func( $this->callback, parent::current() );
	}

}
