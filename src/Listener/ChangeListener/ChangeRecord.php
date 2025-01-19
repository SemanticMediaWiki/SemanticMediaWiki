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
	 *
	 * @param array $container
	 */
	public function __construct( array $container = [] ) {
		$this->container = $container;
	}

	/**
	 * @since 3.2
	 *
	 * @param mixed $key
	 *
	 * @return bool
	 */
	public function has( $key ): bool {
		try {
			$this->seek( $key );
		} catch ( OutOfBoundsException $e ) {
			return false;
		}

		return true;
	}

	/**
	 * @since 3.2
	 *
	 * @param mixed $key
	 *
	 * @return mixed
	 * @throws RuntimeException
	 */
	public function get( $key ) {
		try {
			$this->seek( $key );
		} catch ( OutOfBoundsException $e ) {
			throw new RuntimeException( "There is no `$key` key available!" );
		}

		return $this->current();
	}

}
