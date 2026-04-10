<?php

namespace SMW\DataValues\ValueParsers;

use SMW\DataValues\AllowsListValue;
use SMW\MediaWiki\MediaWikiNsContentReader;

/**
 * @private
 *
 * @license GPL-2.0-or-later
 * @since 2.5
 *
 * @author mwjames
 */
class AllowsListValueParser implements ValueParser {

	private array $errors = [];

	private static array $contents = [];

	/**
	 * @since 2.5
	 */
	public function __construct( private readonly MediaWikiNsContentReader $mediaWikiNsContentReader ) {
	}

	/**
	 * @since 2.5
	 *
	 * @return array
	 */
	public function getErrors(): array {
		return $this->errors;
	}

	/**
	 * @since 3.0
	 */
	public function clear(): void {
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

	private function parse_contents( $userValue, $contents ): array {
		if ( $contents === '' ) {
			$error = [ 'smw-datavalue-allows-value-list-unknown', $userValue ];
			$this->errors[] = $error;
			return $error;
		}

		if ( $contents[0] === '{' && ( $list = json_decode( $contents, true ) ) && is_array( $list ) ) {
			return $list;
		}

		return $this->parse_string( $userValue, $contents );
	}

	/**
	 * @return string[]
	 */
	private function parse_string( $userValue, $contents ): array {
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
				[ $reference, $val ] = explode( '|', $part, 2 );
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
