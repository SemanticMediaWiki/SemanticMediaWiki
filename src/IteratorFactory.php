<?php

namespace SMW;

use Iterator;
use SMW\Iterators\AppendIterator;
use SMW\Iterators\ChunkedIterator;
use SMW\Iterators\CsvFileIterator;
use SMW\Iterators\MappingIterator;
use SMW\Iterators\ResultIterator;
use Wikimedia\Rdbms\ResultWrapper;

/**
 * @license GPL-2.0-or-later
 * @since 2.5
 *
 * @author mwjames
 */
class IteratorFactory {

	/**
	 * @since 2.5
	 */
	public function newResultIterator(
		ResultWrapper|Iterator|array $res
	): ResultIterator {
		return new ResultIterator( $res );
	}

	/**
	 * @since 2.5
	 */
	public function newMappingIterator(
		ResultIterator|Iterator|array $iterable,
		callable $callback
	): MappingIterator {
		return new MappingIterator( $iterable, $callback );
	}

	/**
	 * @since 3.0
	 */
	public function newChunkedIterator(
		ResultIterator|Iterator|array $iterable,
		int $chunkSize = 500
	): ChunkedIterator {
		return new ChunkedIterator( $iterable, $chunkSize );
	}

	/**
	 * @since 3.0
	 */
	public function newAppendIterator(): AppendIterator {
		return new AppendIterator();
	}

	/**
	 * @since 3.0
	 */
	public function newCsvFileIterator(
		string $file,
		bool $parseHeader = false,
		string $delimiter = "\t",
		int $length = 8000
	): CsvFileIterator {
		return new CsvFileIterator( $file, $parseHeader, $delimiter, $length );
	}

}
