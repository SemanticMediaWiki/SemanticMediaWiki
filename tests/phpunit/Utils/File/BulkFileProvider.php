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

		$path = str_replace( array( '\\', '/' ), DIRECTORY_SEPARATOR, $path );

		if ( is_readable( $path ) ) {
			return $path;
		}

		throw new RuntimeException( "Expected an accessible {$path} path" );
	}

	private function iterateToFindFilesForExtension( $path, $extension ) {

		$files = array();

		$directoryIterator = new \RecursiveDirectoryIterator( $path );

		foreach ( new \RecursiveIteratorIterator( $directoryIterator ) as $fileInfo ) {
			if ( strtolower( substr( $fileInfo->getFilename(), -( strlen( $extension ) + 1 ) ) ) === ( '.' . $extension ) ) {
				$files[$fileInfo->getFilename()] = $fileInfo->getPathname();
			}
		}

		return $files;
	}

}
