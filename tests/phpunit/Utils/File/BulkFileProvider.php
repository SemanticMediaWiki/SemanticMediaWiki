<?php

namespace SMW\Tests\Utils\File;

use RuntimeException;

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

		$path = $this->checkPathReadability( $this->path );

		return $this->iterateToFindFilesForExtension(
			$path,
			$this->extension
		);
	}

	private function checkPathReadability( $path ) {

		$path = str_replace( [ '\\', '/' ], DIRECTORY_SEPARATOR, $path );

		if ( is_readable( $path ) ) {
			return $path;
		}

		throw new RuntimeException( "Expected an accessible {$path} path" );
	}

	private function iterateToFindFilesForExtension( $path, $extension ) {

		$files = [];

		$directoryIterator = new \RecursiveDirectoryIterator( $path );

		foreach ( new \RecursiveIteratorIterator( $directoryIterator ) as $fileInfo ) {
			if ( strtolower( substr( $fileInfo->getFilename(), -( strlen( $extension ) + 1 ) ) ) === ( '.' . $extension ) ) {

				// Use a shortcut to be sortable while keep files with same name
				// in different directories distinct
				$files[$fileInfo->getFilename() . ' (' . substr( md5( $fileInfo->getPathname() ), 0, 5 ) .')'] = $fileInfo->getPathname();
			}
		}

		asort( $files );

		return $files;
	}

}
