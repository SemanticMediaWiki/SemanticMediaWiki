<?php

namespace SMW\Tests\Utils\File;

use RuntimeException;

/**
 * @license GNU GPL v2+
 * @since 2.1
 *
 * @author mwjames
 */
class DummyFileCreator {

	/**
	 * @var string
	 */
	private $desiredDestName;

	/**
	 * @var string
	 */
	private $file = '';

	/**
	 * @var integer
	 */
	private $size = 100;

	/**
	 * @since 2.1
	 *
	 * @param string $desiredDestName
	 *
	 * @return string
	 */
	public function createEmptyFile( $desiredDestName ) {
		$this->file = $this->createFile( $desiredDestName );
	}

	/**
	 * @since 2.1
	 *
	 * @param string $desiredDestName
	 * @param string $contentCopyPath
	 *
	 * @return string
	 */
	public function createFileWithCopyFrom( $desiredDestName, $contentCopyPath ) {
		$this->file = $this->createFile( $desiredDestName, file_get_contents( $this->getFile( $contentCopyPath ) ) );
	}

	/**
	 * @since 2.1
	 *
	 * @return string
	 */
	public function getPath() {
		return $this->file;
	}

	/**
	 * @since 2.1
	 */
	public function delete() {
		unlink( $this->file );
	}

	private function createFile( $desiredDestName, $content = '' ) {

		$filename = $this->getLocationForTemporaryFile( $desiredDestName );

		$fh = fopen( $filename, 'w' );

		if ( $content === '' ) {
			ftruncate( $fh, $this->size );
		} else {
			fwrite( $fh, $content );
		}

		fclose( $fh );

		return $this->getFile( $filename );
	}

	private function getLocationForTemporaryFile( $desiredDestName ) {
		return sys_get_temp_dir() . '/' . $desiredDestName;
	}

	private function getFile( $path ) {

		$path = str_replace( [ '\\', '/' ], DIRECTORY_SEPARATOR, $path );

		if ( is_readable( $path ) ) {
			return $path;
		}

		throw new RuntimeException( "Expected an accessible {$path}" );
	}

}
