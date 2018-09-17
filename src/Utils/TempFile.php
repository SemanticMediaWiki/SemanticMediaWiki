<?php

namespace SMW\Utils;

use RuntimeException;

/**
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class TempFile extends File {

	/**
	 * @since 3.0
	 *
	 * @return string
	 * @throws RuntimeException
	 */
	public function generate() {

		$args = func_get_args();
		$key = array_shift( $args );

		if ( $args === [] ) {
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

		$tmpDir = [];
		$path = '';

		if ( isset( $GLOBALS['wgTmpDirectory'] ) ) {
			$tmpDir[] = $GLOBALS['wgTmpDirectory'];
		}

		$tmpDir[] = sys_get_temp_dir();
		$tmpDir[] = ini_get( 'upload_tmp_dir' );

		foreach ( $tmpDir as $tmp ) {
			if ( $tmp != '' && is_dir( $tmp ) && is_writable( $tmp ) ) {
				$path = $tmp;
				break;
			}
		}

		if ( $path !== '' ) {
			return str_replace( [ '\\', '/' ], DIRECTORY_SEPARATOR, $path . '/' . $file );
		}

		throw new RuntimeException( 'No writable temporary directory could be found.' );
	}

}
