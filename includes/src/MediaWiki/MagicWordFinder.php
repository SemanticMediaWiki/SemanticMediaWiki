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
class MagicWordFinder {

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
	 * Remove relevant SMW magic words from the given text and return
	 * an array of the names of all discovered magic words.
	 *
	 * @since 2.0
	 *
	 * @param $magicWord
	 * @param &$text
	 *
	 * @return array
	 */
	public function matchAndRemove( $magicWord, &$text ) {

		$words = array();

		$mw = MagicWord::get( $magicWord );

		if ( $mw->matchAndRemove( $text ) ) {
			$words[] = $magicWord;
		}

		return $words;
	}

	/**
	 * @since 2.0
	 *
	 * @param array $words
	 */
	public function pushMagicWordsToParserOutput( array $words ) {

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
