<?php

namespace SMW\Tests\Utils\File;

use RuntimeException;

/**
 * @license GNU GPL v2+
 * @since 2.1
 *
 * @author mwjames
 */
class JsonFileReader {

	/**
	 * @var string|null
	 */
	private $file = null;

	/**
	 * @var array|null
	 */
	private $contents = null;

	/**
	 * @since 2.1
	 *
	 * @param string|null $file
	 */
	public function __construct( $file = null ) {
		$this->setFile( $file );
	}

	/**
	 * @since 2.1
	 *
	 * @param string $file
	 */
	public function setFile( $file ) {
		$this->file = str_replace( [ '\\', '/' ], DIRECTORY_SEPARATOR, $file );
		$this->contents = null;
	}

	/**
	 * @since 2.1
	 *
	 * @return array
	 * @throws RuntimeException
	 */
	public function read() {

		if ( $this->contents === null && $this->isReadable() ) {
			$this->contents = $this->decodeJsonFileContentsToArray( $this->file );
		}

		if ( $this->contents !== null ) {
			return $this->contents;
		}

		throw new RuntimeException( "Expected a readable {$this->file} file" );
	}

	/**
	 * @since 2.1
	 *
	 * @return boolean
	 */
	public function isReadable() {
		return is_file( $this->file ) && is_readable( $this->file );
	}

	/**
	 * @since 2.1
	 *
	 * @return integer
	 * @throws RuntimeException
	 */
	public function getModificationTime() {

		if ( $this->isReadable() ) {
			return filemtime( $this->file );
		}

		throw new RuntimeException( "Expected a readable {$this->file} file" );
	}

	private function decodeJsonFileContentsToArray( $file ) {

		$json = file_get_contents( $file );

		$json = preg_replace(
			'~ ("(?:[^\\\"]+|\\\.)*") |' . // preserve strings
			'/\* (?:[^*]+|\*+(?!/))* \*/ |' .      // strip multi-line comments
			'//\V* ~sx',                           // strip //-comments
			'$1', $json );

		$contents = json_decode( $json, true );

		if ( $contents !== null && json_last_error() === JSON_ERROR_NONE ) {
			return $contents;
		}

		throw new RuntimeException( $this->printDescriptiveJsonError( json_last_error() ) );
	}

	private function printDescriptiveJsonError( $errorCode ) {

		$errorMessages = [
			JSON_ERROR_STATE_MISMATCH => 'Underflow or the modes mismatch, malformed JSON',
			JSON_ERROR_CTRL_CHAR => 'Unexpected control character found, possibly incorrectly encoded',
			JSON_ERROR_SYNTAX => 'Syntax error, malformed JSON',
			JSON_ERROR_UTF8   => 'Malformed UTF-8 characters, possibly incorrectly encoded',
			JSON_ERROR_DEPTH  => 'The maximum stack depth has been exceeded'
		];

		return sprintf(
			"Expected a JSON compatible format but failed with '%s'",
			$errorMessages[$errorCode]
		);
	}

}
