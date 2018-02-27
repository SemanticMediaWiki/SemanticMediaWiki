<?php

namespace SMW\Utils;

use RuntimeException;

/**
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class TempFile {

	/**
	 * @since 3.0
	 *
	 * @param string $file
	 * @param string $content
	 */
	public function write( $file, $contents ) {
		file_put_contents( $file, $contents );
	}

	/**
	 * @since 3.0
	 *
	 * @param string $file
	 * @param integer|null $checkSum
	 *
	 * @return string
	 * @throws RuntimeException
	 */
	public function read( $file, $checkSum = null ) {

		if ( !is_readable( $file ) ) {
			throw new RuntimeException( "$file is not readable." );
		}

		if ( $checkSum !== null && $this->getCheckSum( $file ) !== $checkSum ) {
			throw new RuntimeException( "Processing of $file failed with a checkSum error." );
		}

		return file_get_contents( $file );
	}

	/**
	 * @since 3.0
	 *
	 * @param string $file
	 */
	public function delete( $file ) {
		@unlink( $file );
	}

	/**
	 * @since 3.0
	 *
	 * @param string $file
	 *
	 * @return integer
	 */
	public function getCheckSum( $file ) {
		return md5_file( $file );
	}

	/**
	 * @since 3.0
	 *
	 * @return string
	 * @throws RuntimeException
	 */
	public function generate() {

		$args = func_get_args();
		$key = array_shift( $args );

		if ( $args === array() ) {
			$key = '';
		}

		return $this->get(
			$key . substr( base_convert( md5( json_encode( $args ) ), 16, 32 ), 0, 12 )
		);
	}

	/**
	 * @since 3.0
	 *
	 * @param string $file
	 *
	 * @return string
	 * @throws RuntimeException
	 */
	public function get( $file ) {

		$tmpDir = array();
		$path = '';

		$tmpDir[] = $GLOBALS['wgTmpDirectory'];
		$tmpDir[] = sys_get_temp_dir();
		$tmpDir[] = ini_get( 'upload_tmp_dir' );

		foreach ( $tmpDir as $tmp ) {
			if ( $tmp != '' && is_dir( $tmp ) && is_writable( $tmp ) ) {
				$path = $tmp;
				break;
			}
		}

		if ( $path !== '' ) {
			return str_replace( array( '\\', '/' ), DIRECTORY_SEPARATOR, $path . '/' . $file );
		}

		throw new RuntimeException( 'No writable temporary directory could be found.' );
	}

}
