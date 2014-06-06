<?php

namespace SMW;

use RuntimeException;
use UnexpectedValueException;

/**
 * @ingroup SMW
 *
 * @license GNU GPL v2+
 * @since 1.9.3
 *
 * @author mwjames
 */
class JsonFileReader {

	protected $path = null;
	protected $contents = null;

	private $asArray = true;

	/**
	 * @since 1.9.3
	 *
	 * @param string|null $path
	 */
	public function __construct( $path = null ) {
		$this->path = $path;
	}

	/**
	 * @since 1.9.3
	 *
	 * @return array
	 * @throws RuntimeException
	 * @throws UnexpectedValueException
	 */
	public function getContents() {

		if ( $this->contents === null ) {
			$this->contents = $this->removeNonRelevantContentComments(
				$this->decodeJsonFileContents(
					$this->isReadableOrThrowException( $this->path )
				)
			);
		}

		return $this->contents;
	}

	/**
	 * @since 1.9.3
	 *
	 * @return integer
	 */
	public function getModificationTime() {
		return filemtime( $this->isReadableOrThrowException( $this->path ) );
	}

	protected function isReadableOrThrowException( $file ) {

		$file = str_replace( array( '\\', '/' ), DIRECTORY_SEPARATOR, $file );

		if ( is_readable( $file ) ) {
			return $file;
		}

		throw new RuntimeException( "Expected a {$file} file" );
	}

	protected function removeNonRelevantContentComments( $contents ) {

		if ( $this->asArray ) {
			unset( $contents['@metadata'] );
		}

		return $contents;
	}

	protected function decodeJsonFileContents( $file ) {

		$contents = json_decode( file_get_contents( $file ), $this->asArray );

		if ( $contents !== null && is_array( $contents ) && json_last_error() === JSON_ERROR_NONE ) {
			return $contents;
		}

		throw new UnexpectedValueException( sprintf(
			"Expected a JSON compatible format but failed with '%s'",
			$this->findDescriptiveJsonError( json_last_error() )
		) );
	}

	private function findDescriptiveJsonError( $errorCode ) {

		$errorMessages = array(
			JSON_ERROR_STATE_MISMATCH => 'Underflow or the modes mismatch, malformed JSON',
			JSON_ERROR_CTRL_CHAR => 'Unexpected control character found, possibly incorrectly encoded',
			JSON_ERROR_SYNTAX => 'Syntax error, malformed JSON',
			JSON_ERROR_UTF8   => 'Malformed UTF-8 characters, possibly incorrectly encoded',
			JSON_ERROR_DEPTH  => 'The maximum stack depth has been exceeded'
		);

		return $errorMessages[ $errorCode ];
	}

}
