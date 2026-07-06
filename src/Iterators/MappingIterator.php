<?php

namespace SMW\Iterators;

use ArrayIterator;
use Countable;
use Iterator;
use IteratorIterator;
use ReturnTypeWillChange;

/**
 * This iterator is expected to be called in combination with another iterator
 * (or traversable/array) in order to apply a mapping on the returned current element
 * during an iterative (foreach etc.) process.
 *
 * @license GPL-2.0-or-later
 * @since 2.5
 *
 * @author mwjames
 */
class MappingIterator extends IteratorIterator implements Countable {

	/**
	 * @var callable
	 */
	private $callback;

	private int $count = 1;

	/**
	 * @since 2.5
	 */
	public function __construct( ResultIterator|Iterator|array $iterable, callable $callback ) {
		if ( is_array( $iterable ) ) {
			$iterable = new ArrayIterator( $iterable );
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
	public function count(): int {
		return $this->count;
	}

	/**
	 * @since 2.5
	 *
	 * {@inheritDoc}
	 */
	#[ReturnTypeWillChange]
	public function current() {
		return call_user_func( $this->callback, parent::current() );
	}

}
