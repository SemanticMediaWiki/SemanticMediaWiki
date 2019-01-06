<?php

namespace SMW\Utils;

use RuntimeException;

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
	 * @since 3.1
	 *
	 * @param string $dir
	 */
	public function __construct( $dir = '' ) {
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
	 * @param string $extension
	 *
	 * @return Iterator
	 */
	public function findByExtension( $extension ) {

		if ( !is_dir( $this->dir ) ) {
			throw new RuntimeException( "Unable to access {$this->dir}!" );
		}

		$iterator = new \RecursiveIteratorIterator(
			new \RecursiveDirectoryIterator( $this->dir )
		);

		return new \RegexIterator( $iterator, '/^.+\.' . $extension . '$/i', \RecursiveRegexIterator::GET_MATCH );
	}

}
