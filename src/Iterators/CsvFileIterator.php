<?php

namespace SMW\Iterators;

use Countable;
use Iterator;
use ReturnTypeWillChange;
use RuntimeException;
use SMW\Exception\FileNotFoundException;
use SplFileObject;

/**
 * @see http://php.net/manual/en/function.fgetcsv.php
 *
 * @license GPL-2.0-or-later
 * @since 3.0
 */
class CsvFileIterator implements Iterator, Countable {

	private ?SplFileObject $file = null;

	private array $header = [];

	private int $key = 0;

	private int $count = 0;

	/**
	 * @since 3.0
	 */
	public function __construct(
		string $file,
		private $parseHeader = false,
		private $delimiter = ",
		",
		private $length = 8000,
	) {
		try {
			$this->file = new SplFileObject( $file, 'r' );
		} catch ( RuntimeException ) {
			throw new FileNotFoundException( 'File "' . $file . '" is not accessible.' );
		}
	}

	/**
	 * @see Countable::count
	 * @since 2.5
	 *
	 * {@inheritDoc}
	 */
	public function count(): int {
		if ( $this->count ) {
			return $this->count;
		}

		// https://stackoverflow.com/questions/21447329/how-can-i-get-the-total-number-of-rows-in-a-csv-file-with-php
		$this->file->seek( PHP_INT_MAX );
		$this->count = $this->file->key() + 1;
		$this->file->rewind();

		return $this->count;
	}

	/**
	 * @since 3.0
	 */
	public function getHeader(): array {
		return $this->header;
	}

	/**
	 * Resets the file handle
	 *
	 * @since 3.0
	 *
	 * {@inheritDoc}
	 */
	public function rewind(): void {
		$this->key = 0;
		$this->file->rewind();
	}

	/**
	 * Returns the current CSV row as a 2 dimensional array
	 *
	 * @since 3.0
	 *
	 * {@inheritDoc}
	 */
	#[ReturnTypeWillChange]
	public function current() {
		// First iteration to match the header
		if ( $this->parseHeader && $this->key == 0 ) {
			$header = $this->file->fgetcsv( $this->delimiter, '"', '\\' );
			$this->header = is_array( $header ) ? $header : [];
		}

		$currentElement = $this->file->fgetcsv( $this->delimiter, '"', '\\' );
		$this->key++;

		return $currentElement;
	}

	/**
	 * Returns the current row number.
	 *
	 * @since 3.0
	 *
	 * {@inheritDoc}
	 */
	public function key(): int {
		return $this->key;
	}

	/**
	 * Checks if the end of file is reached.
	 *
	 * @since 3.0
	 *
	 * {@inheritDoc}
	 * @suppress PhanParamSignatureMismatchInternal
	 */
	#[ReturnTypeWillChange]
	public function next(): bool {
		return !$this->file->eof();
	}

	/**
	 * Checks if the next row is a valid row.
	 *
	 * @since 3.0
	 *
	 * {@inheritDoc}
	 */
	public function valid(): bool {
		if ( $this->next() ) {
			return true;
		}

		return false;
	}

}
