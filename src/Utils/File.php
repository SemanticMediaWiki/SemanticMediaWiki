<?php

namespace SMW\Utils;

use RuntimeException;

/**
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class File {

	/**
	 * @since 3.0
	 *
	 * @param string $file
	 * @param string $content
	 * @param integer $flags
	 */
	public function write( $file, $contents, $flags = 0 ) {
		file_put_contents( $file, $contents, $flags );
	}

	/**
	 * @since 3.0
	 *
	 * @param string $file
	 *
	 * @return boolean
	 */
	public function exists( $file ) {
		return file_exists( $file );
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

}
