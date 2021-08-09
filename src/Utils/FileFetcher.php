<?php

namespace SMW\Utils;

use RuntimeException;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;
use RegexIterator;
use RecursiveRegexIterator;

/**
 * @license GNU GPL v2+
 * @since 3.1
 *
 * @author mwjames
 */
class FileFetcher {

	/**
	 * @var string
	 */
	private $dir = '';

	/**
	 * @var int
	 */
	private $maxDepth = -1;

	/**
	 * @var string
	 */
	private $sort;

	/**
	 * @since 3.1
	 *
	 * @param string $dir
	 */
	public function __construct( string $dir = '' ) {
		$this->dir = $dir;
	}

	/**
	 * @since 3.1
	 *
	 * @param string $dir
	 */
	public function setDir( $dir ) {
		$this->dir = $dir;
	}

	/**
	 * @since 3.1
	 *
	 * @param string $sort
	 */
	public function sort( $sort ) {

		$sort = strtolower( $sort );

		if ( in_array( $sort, [ 'asc', 'desc' ] ) ) {
			$this->sort = $sort;
		}
	}

	/**
	 * @since 3.1
	 *
	 * @param string $file
	 *
	 * @return string
	 */
	public static function normalize( $file ) {
		return str_replace( [ '\\', '//', '/', '\\\\' ], DIRECTORY_SEPARATOR, $file );
	}

	/**
	 * @since 3.2
	 *
	 * @param int $maxDepth
	 */
	public function setMaxDepth( int $maxDepth ) {
		$this->maxDepth = $maxDepth;
	}

	/**
	 * @since 3.1
	 *
	 * @param string $extension
	 *
	 * @return Iterator
	 */
	public function findByExtension( $extension ) {

		if ( !is_dir( $this->dir ) ) {
			throw new RuntimeException( "Unable to access {$this->dir}!" );
		}

		$iterator = new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator( $this->dir )
		);

		$iterator->setMaxDepth( $this->maxDepth );

		$matches = new RegexIterator(
			$iterator, '/^.+\.' . $extension . '$/i',
			RecursiveRegexIterator::GET_MATCH
		);

		if ( $this->sort !== null ) {
			$matches = iterator_to_array(
				$matches
			);

			/**
			 * @uses sort_asc
			 * @uses sort_desc
			 */
			usort( $matches, [ $this, "sort_$this->sort" ] );
		}

		return $matches;
	}

	private function sort_asc( $a, $b ) {
		return strnatcasecmp( $a[0], $b[0] );
	}

	private function sort_desc( $a, $b ) {
		return strnatcasecmp( $b[0], $a[0] );
	}

}
