<?php

namespace SMW\Listener\ChangeListener;

use Iterator;
use OutOfBoundsException;
use RuntimeException;
use SeekableIterator;
use SMW\Iterators\DotSeekableIteratorTrait;

/**
 * @license GPL-2.0-or-later
 * @since 3.2
 *
 * @author mwjames
 */
class ChangeRecord implements Iterator, SeekableIterator {

	use DotSeekableIteratorTrait;

	/**
	 * @since 3.2
	 */
	public function __construct( array $container = [] ) {
		$this->container = $container;
	}

	/**
	 * @since 3.2
	 */
	public function has( mixed $key ): bool {
		try {
			$this->seek( $key );
		} catch ( OutOfBoundsException ) {
			return false;
		}

		return true;
	}

	/**
	 * @since 3.2
	 *
	 * @throws RuntimeException
	 */
	public function get( mixed $key ): mixed {
		try {
			$this->seek( $key );
		} catch ( OutOfBoundsException ) {
			throw new RuntimeException( "There is no `$key` key available!" );
		}

		return $this->current();
	}

}
