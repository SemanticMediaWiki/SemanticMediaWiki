<?php

namespace SMW\Utils;

/**
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class Csv {

	const DEFAULT_SEP = ',';

	/**
	 * @var boolean
	 */
	private $show = false;

	/**
	 * @var boolean
	 */
	private $bom = false;

	/**
	 * @since 3.0
	 *
	 * @param boolean $show
	 * @param boolean $bom
	 */
	public function __construct( $show = false, $bom = false ) {
		$this->show = $show;
		$this->bom = $bom;
	}

	/**
	 * @since 3.0
	 *
	 * @param array $header
	 * @param array $rows
	 * @param string $sep
	 *
	 * @return string
	 */
	public function toString( array $header, array $rows, $sep = self::DEFAULT_SEP ) {

		$handle = fopen( 'php://temp', 'r+' );

		// fputcsv(): delimiter must be a single character
		$sep = $sep !== '' ? $sep{0} : self::DEFAULT_SEP;

		// https://en.wikipedia.org/wiki/Comma-separated_values#Standardization
		// http://php.net/manual/en/function.fputcsv.php
		if ( $this->bom ) {
			fputs( $handle, ( chr( 0xEF ) . chr( 0xBB ) . chr( 0xBF ) ) );
		}

		// https://en.wikipedia.org/wiki/Comma-separated_values#Application_support
		if ( $this->show ) {
			fputs( $handle, "sep=" . $sep . "\n" );
		}

		if ( $header !== [] ) {
			fputcsv( $handle, $header, $sep );
		}

		foreach ( $rows as $row ) {
			fputcsv( $handle, $row, $sep );
		}

		rewind( $handle );

		return stream_get_contents( $handle );
	}

	/**
	 * Merge row and column values where the subject (first column) uses the same
	 * identifier.
	 *
	 * @since 3.0
	 *
	 * @param array $rows
	 * @param string $sep
	 *
	 * @return array
	 */
	public function merge( $rows, $sep = ',' ) {

		$map = [];
		$order = [];

		foreach ( $rows as $key => $row ) {

			// First column is used to build the hash index to find rows with
			// the same hash
			$hash = md5( $row[0] );

			// Retain the order
			if ( !isset( $order[$hash] ) ) {
				$order[$hash] = $key;
			}

			if ( !isset( $map[$hash] ) ) {
				$map[$hash] = $row;
			} else {
				$concat = [];

				foreach ( $map[$hash] as $k => $v ) {
					// Index 0 represents the first column, same hash, only
					// concatenate the rest of the columns
					if ( $k != 0 ) {
						$v = $v . ( isset( $row[$k] ) ? "$sep" . $row[$k] : '' );
						// Filter duplicate values
						$v = array_flip( explode( $sep, $v ) );
						// Make it a simple list
						$v = implode( $sep, array_keys( $v ) );
					}

					$concat[$k] = $v;
				}

				$map[$hash] = $concat;
			}
		}

		$order = array_flip( $order );

		foreach ( $order as $key => $hash ) {
			$order[$key] = $map[$hash];
		}

		return $order;
	}

}
