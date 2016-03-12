<?php

namespace SMW\DataValues\ValueParsers;

use SMW\MediaWiki\MediaWikiNsContentReader;

/**
 * @private
 *
 * @license GNU GPL v2+
 * @since 2.4
 *
 * @author mwjames
 */
class AllowsPatternContentParser implements ValueParser {

	/**
	 * @var MediaWikiNsContentReader
	 */
	private $mediaWikiNsContentReader;

	/**
	 * @var array
	 */
	private $errors = array();

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

		$contentList = $this->doParseContent(
			$this->mediaWikiNsContentReader->read( 'smw allows pattern' )
		);

		if ( !isset( $contentList[$userValue] ) ) {
			return false;
		}

		return $contentList[$userValue];
	}

	private function doParseContent( $contents ) {

		$list = array();

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
