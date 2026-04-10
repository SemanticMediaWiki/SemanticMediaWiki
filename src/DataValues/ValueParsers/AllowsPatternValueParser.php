<?php

namespace SMW\DataValues\ValueParsers;

use SMW\DataValues\AllowsPatternValue;
use SMW\MediaWiki\MediaWikiNsContentReader;

/**
 * @private
 *
 * @license GPL-2.0-or-later
 * @since 2.4
 *
 * @author mwjames
 */
class AllowsPatternValueParser implements ValueParser {

	private array $errors = [];

	/**
	 * @since 2.4
	 */
	public function __construct( private readonly MediaWikiNsContentReader $mediaWikiNsContentReader ) {
	}

	/**
	 * @since 2.4
	 *
	 * @return array
	 */
	public function getErrors(): array {
		return $this->errors;
	}

	/**
	 * @since 2.4
	 *
	 * @param string $userValue
	 *
	 * @return string|false
	 */
	public function parse( $userValue ) {
		$this->errors = [];

		$contentList = $this->doParseContent(
			$this->mediaWikiNsContentReader->read( AllowsPatternValue::REFERENCE_PAGE_ID )
		);

		if ( !isset( $contentList[$userValue] ) ) {
			return false;
		}

		return $contentList[$userValue];
	}

	private function doParseContent( $contents ): ?array {
		$list = [];

		if ( $contents === '' ) {
			return null;
		}

		$contents = $contents ?? '';
		$parts = array_map( 'trim', preg_split( "([\n][\s]?)", $contents ) );

		// Get definition from first line
		array_shift( $parts );

		foreach ( $parts as $part ) {

			if ( strpos( $part, '|' ) === false ) {
				continue;
			}

			[ $reference, $regex ] = explode( '|', $part, 2 );
			$list[trim( $reference )] = $regex;
		}

		return $list;
	}

}
