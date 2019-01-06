<?php

namespace SMW\Tests\Utils\File;

use RuntimeException;
use SMW\Utils\FileFetcher;

/**
 * @license GNU GPL v2+
 * @since 2.1
 *
 * @author mwjames
 */
class BulkFileProvider {

	/**
	 * @var string
	 */
	private $path = null;

	/**
	 * @var string
	 */
	private $extension = 'json';

	/**
	 * @since 2.1
	 *
	 * @param string $path
	 */
	public function __construct( $path ) {
		$this->path = $path;
	}

	/**
	 * @since 2.1
	 *
	 * @param string $extension
	 */
	public function searchByFileExtension( $extension ) {
		$this->extension = $extension;
	}

	/**
	 * @since 2.1
	 *
	 * @return array
	 */
	public function getFiles() {

		$fileFetcher = new FileFetcher( $this->path );
		$iterator = $fileFetcher->findByExtension( $this->extension );

		$files = [];

		foreach ( $iterator as $file => $value ) {
			$fileInfo = pathinfo( $file );
			$files[$fileInfo['filename'] . ' (' . substr( md5( $file ), 0, 5 ) .')'] = $file;
		}

		asort( $files );

		return $files;
	}

}
