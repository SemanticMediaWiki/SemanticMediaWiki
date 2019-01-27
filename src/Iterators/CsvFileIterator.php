<?php

namespace SMW\Iterators;

use Exception;
use Iterator;
use Countable;
use SMW\Exception\FileNotFoundException;
use RuntimeException;
use SplFileObject;

/**
 * @see http://php.net/manual/en/function.fgetcsv.php
 *
 * @license GNU GPL v2+
 * @since 3.0
 */
class CsvFileIterator implements Iterator, Countable {

	/**
	 * @var SplFileObject
	 */
	private $file;

	/**
	 * @var Resource
	 */
	private $handle;

	/**
	 * @var boolean
	 */
	private $parseHeader;

	/**
	 * @var []
	 */
	private $header = [];

	/**
	 * @var string
	 */
	private $delimiter;

	/**
	 * @var integer
	 */
	private $length;

	/**
	 * @var int
	 */
	private $key = 0;

	/**
	 * @var boolean
	 */
	private $count = false;

	/**
	 * @since 3.0
	 *
	 * @param string $file
	 * @param boolean $parseHeader
	 * @param string  $delimiter
	 * @param integer $length
	 */
	public function __construct( $file, $parseHeader = false, $delimiter = ",", $length = 8000 ) {

		try {
			$this->file = new SplFileObject( $file , 'r' );
		} catch ( RuntimeException $e ) {
			throw new FileNotFoundException( 'File "'. $file . '" is not accessible.' );
		}

		$this->parseHeader = $parseHeader;
		$this->delimiter = $delimiter;
		$this->length = $length;
	}

	/**
	 * @since 3.0
	 */
	public function __destruct() {
		$this->handle = null;
	}
	/**
	 * @see Countable::count
	 * @since 2.5
	 *
	 * {@inheritDoc}
	 */
	public function count() {

		if ( $this->count ) {
			return $this->count;
		}

		// https://stackoverflow.com/questions/21447329/how-can-i-get-the-total-number-of-rows-in-a-csv-file-with-php
		$this->file->seek(PHP_INT_MAX);
		$this->count = $this->file->key() + 1;
		$this->file->rewind();

		return $this->count;
	}

	/**
	 * @since 3.0
	 *
	 * @return []
	 */
	public function getHeader() {
		return $this->header;
	}

	/**
	 * Resets the file handle
	 *
	 * @since 3.0
	 *
	 * {@inheritDoc}
	 */
	public function rewind() {
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
	public function current() {

		// First iteration to match the header
		if ( $this->parseHeader && $this->key == 0 ) {
			$this->header = $this->file->fgetcsv( $this->delimiter );
		}

		$currentElement =  $this->file->fgetcsv( $this->delimiter );
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
	public function key() {
		return $this->key;
	}

	/**
	 * Checks if the end of file is reached.
	 *
	 * @since 3.0
	 *
	 * {@inheritDoc}
	 */
	public function next() {
		return !$this->file->eof();
	}

	/**
	 * Checks if the next row is a valid row.
	 *
	 * @since 3.0
	 *
	 * {@inheritDoc}
	 */
	public function valid() {

		if ( $this->next() ) {
			return true;
		}

		return false;
	}

}
