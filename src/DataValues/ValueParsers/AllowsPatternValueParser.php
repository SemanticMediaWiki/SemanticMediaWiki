<?php

namespace SMW\DataValues\ValueParsers;

use SMW\DataValues\AllowsPatternValue;
use SMW\MediaWiki\MediaWikiNsContentReader;

/**
 * @private
 *
 * @license GNU GPL v2+
 * @since 2.4
 *
 * @author mwjames
 */
class AllowsPatternValueParser implements ValueParser {

	/**
	 * @var MediaWikiNsContentReader
	 */
	private $mediaWikiNsContentReader;

	/**
	 * @var array
	 */
	private $errors = [];

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
	 *
	 * @return array
	 */
	public function getErrors() {
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

	private function doParseContent( $contents ) {

		$list = [];

		if ( $contents === '' ) {
			return null;
		}

		$parts = array_map( 'trim', preg_split( "([\n][\s]?)", $contents ) );

		// Get definition from first line
		array_shift( $parts );

		foreach ( $parts as $part ) {

			if ( strpos( $part, '|' ) === false ) {
				continue;
			}

			list( $reference, $regex ) = explode( '|', $part, 2 );
			$list[trim( $reference )] = $regex;
		}

		return $list;
	}

}
