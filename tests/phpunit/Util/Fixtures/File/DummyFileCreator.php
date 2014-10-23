<?php

namespace SMW\Tests\Util\Fixtures\File;

use SMW\Tests\Util\Mock\MockSuperUser;

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
	 * @var integer
	 */
	private $size = 100;

	/**
	 * @since 2.1
	 *
	 * @param string $desiredDestName
	 */
	public function __construct( $desiredDestName ) {
		$this->desiredDestName = $desiredDestName;
	}

	/**
	 * @since 2.1
	 *
	 * @return string
	 */
	public function createEmptyFile() {
		return $this->createFile();
	}

	/**
	 * @since 2.1
	 *
	 * @param string $contentCopyPath
	 *
	 * @return string
	 */
	public function createFileByCopyContentOf( $contentCopyPath ) {
		return $this->createFile( file_get_contents( $this->canReadFile( $contentCopyPath ) ) );
	}

	private function createFile( $content = '' ) {

		$filename = $this->getLocationForTemporaryFile();

		$fh = fopen( $filename, 'w' );

		if ( $content === '' ) {
			ftruncate( $fh, $this->size );
		} else {
			fwrite( $fh, $content );
		}

		fclose( $fh );

		return $this->canReadFile( $filename );
	}

	private function getLocationForTemporaryFile() {
		return sys_get_temp_dir() . '/' . $this->desiredDestName;
	}

	private function canReadFile( $path ) {

		$path = str_replace( array( '\\', '/' ), DIRECTORY_SEPARATOR, $path );

		if ( is_readable( $path ) ) {
			return $path;
		}

		throw new RuntimeException( "Expected an accessible {$path}" );
	}

}
