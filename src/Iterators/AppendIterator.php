<?php

namespace SMW\Iterators;

use ArrayIterator;
use Countable;
use Iterator;
use RuntimeException;
use Traversable;

/**
 * @see Guzzle::AppendIterator
 * @see https://bugs.php.net/bug.php?id=49104
 *
 * @license GPL-2.0-or-later
 * @since 3.0
 */
class AppendIterator extends \AppendIterator implements Countable {

	/**
	 * @var int
	 */
	private $count = 0;

	/**
	 * @since 3.0
	 *
	 * @param Traversable|array $iterable
	 */
	public function add( $iterable ) {
		if ( is_array( $iterable ) ) {
			$iterable = new ArrayIterator( $iterable );
		}

		if ( !$iterable instanceof Traversable ) {
			throw new RuntimeException( "AppendIterator expected an Traversable" );
		}

		$this->append( $iterable );
	}

	/**
	 * @see Countable::count
	 * @since 3.0
	 *
	 * {@inheritDoc}
	 */
	public function count(): int {
		return $this->count;
	}

	/**
	 * @since 3.0
	 *
	 * {@inheritDoc}
	 */
	public function append( Iterator $iterable ): void {
		if ( $iterable instanceof Countable ) {
			$this->count += $iterable->count();
		}

		$this->getArrayIterator()->append( $iterable );
	}

}
