<?php

namespace SMW\Tests\Utils\File;

use RuntimeException;

/**
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class ContentsReader {

	/**
	 * @since 3.0
	 *
	 * @param string $file
	 *
	 * @return string
	 */
	public static function readContentsFrom( $file ) {

		$file = str_replace( [ '\\', '/' ], DIRECTORY_SEPARATOR, $file );

		if ( !is_readable( $file ) ) {
			throw new RuntimeException( "Could not open or read: $file" );
		}

		$contents = file_get_contents( $file );

		// http://php.net/manual/en/function.file-get-contents.php
		$contents = mb_convert_encoding(
			$contents,
			'UTF-8',
			mb_detect_encoding( $contents, 'UTF-8, ISO-8859-1, ISO-8859-2', true )
		);

		// https://stackoverflow.com/questions/2115549/php-difference-between-r-n-and-n
		return str_replace( "\r\n", "\n", $contents );
	}

}
