<?php

namespace SMW\DataValues\ValueParsers;

use SMW\DataValues\AllowsListValue;
use SMW\MediaWiki\MediaWikiNsContentReader;

/**
 * @private
 *
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class AllowsListValueParser implements ValueParser {

	/**
	 * @var MediaWikiNsContentReader
	 */
	private $mediaWikiNsContentReader;

	/**
	 * @var array
	 */
	private $errors = [];

	/**
	 * @var array
	 */
	private static $contents = [];

	/**
	 * @since 2.5
	 *
	 * @param MediaWikiNsContentReader $mediaWikiNsContentReader
	 */
	public function __construct( MediaWikiNsContentReader $mediaWikiNsContentReader ) {
		$this->mediaWikiNsContentReader = $mediaWikiNsContentReader;
	}

	/**
	 * @since 2.5
	 *
	 * @return array
	 */
	public function getErrors() {
		return $this->errors;
	}

	/**
	 * @since 3.0
	 */
	public function clear() {
		self::$contents = [];
		$this->errors = [];
	}

	/**
	 * @since 2.5
	 *
	 * @param string $userValue
	 *
	 * @return string|false
	 */
	public function parse( $userValue ) {

		$this->errors = [];

		if ( isset( self::$contents[$userValue] ) ) {
			return self::$contents[$userValue];
		}

		self::$contents[$userValue] = $this->parse_contents(
			$userValue,
			$this->mediaWikiNsContentReader->read( AllowsListValue::LIST_PREFIX . $userValue )
		);

		return self::$contents[$userValue];
	}

	private function parse_contents( $userValue, $contents ) {

		if ( $contents === '' ) {
			return $this->errors[] = [ 'smw-datavalue-allows-value-list-unknown', $userValue ];
		}

		if ( $contents{0} === '{' && ( $list = json_decode( $contents, true ) ) && is_array( $list ) ) {
			return $list;
		}

		return $this->parse_string( $userValue, $contents );
	}

	private function parse_string( $userValue, $contents ) {

		$parts = array_map( 'trim', preg_split( "([\n][\s]?)", $contents ) );
		$list = [];

		foreach ( $parts as $part ) {

			// Only recognize those with a * Foo
			if ( strpos( $part, '*' ) === false ) {
				continue;
			}

			// Remove * from the content, other processes may use the hierarchy
			// indicator something else
			$part = trim( str_replace( '*', '', $part ) );

			// Allow something like * Foo|Bar
			if ( strpos( $part, '|' ) !== false ) {
				list( $reference, $val ) = explode( '|', $part, 2 );
				$list[$reference] = $val;
			} else {
				$list[$part] = $part;
			}
		}

		if ( $list === [] ) {
			$this->errors[] = [ 'smw-datavalue-allows-value-list-missing-marker', $userValue ];
		}

		return $list;
	}

}
