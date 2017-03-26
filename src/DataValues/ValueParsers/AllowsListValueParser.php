<?php

namespace SMW\DataValues\ValueParsers;

use SMW\MediaWiki\MediaWikiNsContentReader;
use SMW\DataValues\AllowsListValue;

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
	private $errors = array();

	/**
	 * @var array
	 */
	private static $contents = array();

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
	 * @since 2.5
	 *
	 * @param string $userValue
	 *
	 * @return string|false
	 */
	public function parse( $userValue ) {

		if ( isset( self::$contents[$userValue] ) ) {
			return self::$contents[$userValue];
		}

		$this->errors = array();

		self::$contents[$userValue] = $this->doParseContent(
			$userValue,
			$this->mediaWikiNsContentReader->read( AllowsListValue::LIST_PREFIX . $userValue )
		);

		return self::$contents[$userValue];
	}

	private function doParseContent( $userValue, $contents ) {

		$list = array();

		if ( $contents === '' ) {
			return $this->errors[] = array( 'smw-datavalue-allows-value-list-unknown', $userValue );
		}

		$parts = array_map( 'trim', preg_split( "([\n][\s]?)", $contents ) );

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

		if ( $list === array() ) {
			 $this->errors[] = array( 'smw-datavalue-allows-value-list-missing-marker', $userValue );
		}

		return $list;
	}

}
