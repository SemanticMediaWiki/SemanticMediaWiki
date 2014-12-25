<?php

namespace SMW\Tests\Utils;

use RuntimeException;
use UnexpectedValueException;

/**
 * @ingroup SMW
 *
 * @license GNU GPL v2+
 * @since 2.1
 *
 * @author mwjames
 */
class JsonFileReader {

	/**
	 * @var string
	 */
	private $file = null;

	/**
	 * @var array
	 */
	private $contents = null;

	/**
	 * @var boolean
	 */
	private $convertedToAssociativeArray = true;

	/**
	 * @since 2.1
	 *
	 * @param string|null $file
	 */
	public function __construct( $file = null ) {
		$this->file = $file;
	}

	/**
	 * @since 2.1
	 *
	 * @return array
	 */
	public function getContents() {

		$file = $this->checkFileReadability( $this->file );

		if ( $this->contents === null ) {
			$this->contents = $this->removeNonRelevantContentComments(
				$this->decodeJsonFileContents( $file )
			);
		}

		return $this->contents;
	}

	/**
	 * @since 2.1
	 *
	 * @return integer
	 */
	public function getModificationTime() {
		return filemtime( $this->checkFileReadability( $this->file ) );
	}

	private function checkFileReadability( $file ) {

		$file = str_replace( array( '\\', '/' ), DIRECTORY_SEPARATOR, $file );

		if ( is_file( $file ) && is_readable( $file ) ) {
			return $file;
		}

		throw new RuntimeException( "Expected a {$file} file" );
	}

	private function removeNonRelevantContentComments( $contents ) {

		if ( $this->convertedToAssociativeArray ) {
			unset( $contents['@metadata'] );
		}

		return $contents;
	}

	private function decodeJsonFileContents( $file ) {

		$contents = json_decode( file_get_contents( $file ), $this->convertedToAssociativeArray );

		if ( $contents !== null && is_array( $contents ) && json_last_error() === JSON_ERROR_NONE ) {
			return $contents;
		}

		throw new RuntimeException( $this->getDescriptiveJsonError( json_last_error() ) );
	}

	private function getDescriptiveJsonError( $errorCode ) {

		$errorMessages = array(
			JSON_ERROR_STATE_MISMATCH => 'Underflow or the modes mismatch, malformed JSON',
			JSON_ERROR_CTRL_CHAR => 'Unexpected control character found, possibly incorrectly encoded',
			JSON_ERROR_SYNTAX => 'Syntax error, malformed JSON',
			JSON_ERROR_UTF8   => 'Malformed UTF-8 characters, possibly incorrectly encoded',
			JSON_ERROR_DEPTH  => 'The maximum stack depth has been exceeded'
		);

		return sprintf(
			"Expected a JSON compatible format but failed with '%s'",
			$errorMessages[ $errorCode ]
		);
	}

}
