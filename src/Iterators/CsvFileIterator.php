<?php

namespace SMW\Iterators;

use Exception;
use Iterator;
use SMW\Exception\FileNotFoundException;

/**
 * @see http://php.net/manual/en/function.fgetcsv.php
 *
 * @license GNU GPL v2+
 * @since 3.0
 */
class CsvFileIterator implements Iterator {

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
	 * @since 3.0
	 *
	 * @param string $file
	 * @param boolean $parseHeader
	 * @param string  $delimiter
	 * @param integer $length
	 */
	public function __construct( $file, $parseHeader = false, $delimiter = ",", $length = 8000 ) {

		try {
			$this->handle = fopen( $file, "r" );
		} catch ( Exception $e ) {
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
		if( is_resource( $this->handle ) ) {
			fclose( $this->handle );
		}
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
		rewind( $this->handle );
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
			$this->header = fgetcsv( $this->handle, $this->length, $this->delimiter );
		}

		$currentElement = fgetcsv( $this->handle, $this->length, $this->delimiter );
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
		return !feof( $this->handle );
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

		fclose( $this->handle );
		return false;
	}

}
