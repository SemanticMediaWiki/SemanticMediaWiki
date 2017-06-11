<?php

namespace SMW;

use SMW\Iterators\ResultIterator;
use SMW\Iterators\MappingIterator;
use SMW\Iterators\ChunkedIterator;

/**
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class IteratorFactory {

	/**
	 * @since 2.5
	 *
	 * @param ResultWrapper|array $res
	 *
	 * @return ResultIterator
	 */
	public function newResultIterator( $res ) {
		return new ResultIterator( $res );
	}

	/**
	 * @since 2.5
	 *
	 * @param Iterator/array $$iterable
	 * @param callable $callback
	 *
	 * @return MappingIterator
	 */
	public function newMappingIterator( $iterable, callable $callback ) {
		return new MappingIterator( $iterable, $callback );
	}

	/**
	 * @since 3.0
	 *
	 * @param Iterator/array $$iterable
	 * @param integer $chunkSize
	 *
	 * @return ChunkedIterator
	 */
	public function newChunkedIterator( $iterable, $chunkSize = 500 ) {
		return new ChunkedIterator( $iterable, $chunkSize );
	}

}
