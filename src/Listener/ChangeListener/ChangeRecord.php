<?php

namespace SMW\Listener\ChangeListener;

use Iterator;
use SeekableIterator;
use OutOfBoundsException;
use RuntimeException;
use SMW\Iterators\DotSeekableIteratorTrait;

/**
 * @license GNU GPL v2+
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
	 * @return boolean
	 */
	public function has( $key ) : bool {

		try {
			$this->seek( $key );
		} catch( OutOfBoundsException $e ) {
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
		} catch( OutOfBoundsException $e ) {
			throw new RuntimeException( "There is no `$key` key available!" );
		}

		return $this->current();
	}

}
