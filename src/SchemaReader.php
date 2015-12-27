<?php

namespace SMW;

use SMW\MediaWiki\MediaWikiNsContentReader;
use RuntimeException;

/**
 * @license GNU GPL v2+
 * @since 2.4
 *
 * @author mwjames
 */
class SchemaReader {

	/**
	 * @var MediaWikiNsContentReader
	 */
	private $mediaWikiNsContentReader;

	/**
	 * @var array
	 */
	private static $schemaDefinition = array();

	/**
	 * @var array
	 */
	private $schemas = array();

	/**
	 * @since 2.4
	 *
	 * @param MediaWikiNsContentReader $mediaWikiNsContentReader
	 */
	public function __construct( MediaWikiNsContentReader $mediaWikiNsContentReader ) {
		$this->mediaWikiNsContentReader = $mediaWikiNsContentReader;
	}

	/**
	 * @since 2.4
	 */
	public static function clear() {
		self::$schemaDefinition = array();
	}

	/**
	 * @since 2.4
	 */
	public function skipMessageCache() {
		$this->mediaWikiNsContentReader->skipMessageCache();
	}

	/**
	 * @since 2.4
	 *
	 * @param string $name
	 */
	public function registerSchema( $name ) {
		$this->schemas[] = ucfirst( $name );
	}

	/**
	 * @since 2.4
	 *
	 * @return mixed
	 */
	public function read( $id ) {

		$id = strtolower( trim( $id ) );

		if ( self::$schemaDefinition === array() ) {
			foreach ( $this->schemas as $name ) {
				self::$schemaDefinition = array_merge( self::$schemaDefinition, $this->parseContentFor( $name ) );
			}
		}

		return isset( self::$schemaDefinition[$id] ) ? self::$schemaDefinition[$id] : array();
	}

	/**
	 * @param string $name
	 */
	private function parseContentFor( $name ) {

		$contents = $this->mediaWikiNsContentReader->read( $name );

		if ( $contents === '' || !is_string( $contents ) ) {
			return array();
		}

		$contents = json_decode( $contents, true );

		if ( $contents !== null && json_last_error() === JSON_ERROR_NONE ) {
			return $contents;
		}

		throw new RuntimeException( $this->printDescriptiveJsonError( json_last_error() ) );
	}

	private function printDescriptiveJsonError( $errorCode ) {

		$errorMessages = array(
			JSON_ERROR_STATE_MISMATCH => 'Underflow or the modes mismatch, malformed JSON',
			JSON_ERROR_CTRL_CHAR => 'Unexpected control character found, possibly incorrectly encoded',
			JSON_ERROR_SYNTAX => 'Syntax error, malformed JSON',
			JSON_ERROR_UTF8   => 'Malformed UTF-8 characters, possibly incorrectly encoded',
			JSON_ERROR_DEPTH  => 'The maximum stack depth has been exceeded'
		);

		return sprintf(
			"Expected a JSON compatible format but failed with '%s'",
			$errorMessages[$errorCode]
		);
	}

}
