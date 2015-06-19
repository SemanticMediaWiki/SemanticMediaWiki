<?php

namespace SMW\MediaWiki;

use MagicWord;
use ParserOutput;

/**
 * @license GNU GPL v2+
 * @since 2.0
 *
 * @author mwjames
 */
class MagicWordsFinder {

	/**
	 * @var ParserOutput
	 */
	private $parserOutput = null;

	/**
	 * @since 2.0
	 *
	 * @param ParserOutput|null $parserOutput
	 */
	public function __construct( ParserOutput $parserOutput = null ) {
		$this->parserOutput = $parserOutput;
	}

	/**
	 * @since 2.0
	 *
	 * @param ParserOutput $parserOutput
	 *
	 * @return self
	 */
	public function setOutput( ParserOutput $parserOutput ) {
		$this->parserOutput = $parserOutput;
		return $this;
	}

	/**
	 * Find the magic word and have it removed from the text
	 *
	 * @since 2.0
	 *
	 * @param $magicWord
	 * @param &$text
	 *
	 * @return string
	 */
	public function findMagicWordInText( $magicWord, &$text ) {

		$mw = MagicWord::get( $magicWord );

		if ( $mw->matchAndRemove( $text ) ) {
			return $magicWord;
		}

		return '';
	}

	/**
	 * @since 2.0
	 *
	 * @param array $words
	 */
	public function pushMagicWordsToParserOutput( array $words ) {

		$this->parserOutput->setTimestamp( wfTimestampNow() );

		// Filter empty lines
		$words = array_values( array_filter( $words ) );

		if ( $this->hasExtensionData() ) {
			return $this->parserOutput->setExtensionData( 'smwmagicwords', $words );
		}

		return $this->parserOutput->mSMWMagicWords = $words;
	}

	/**
	 * @since 2.0
	 *
	 * @return array
	 */
	public function getMagicWords() {

		if ( $this->hasExtensionData() ) {
			return $this->parserOutput->getExtensionData( 'smwmagicwords' );
		}

		if ( isset( $this->parserOutput->mSMWMagicWords ) ) {
			return $this->parserOutput->mSMWMagicWords;
		}

		return array();
	}

	/**
	 * FIXME Remove when MW 1.21 becomes mandatory
	 */
	protected function hasExtensionData() {
		return method_exists( $this->parserOutput, 'getExtensionData' );
	}

}
