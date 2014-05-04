<?php

namespace SMW\MediaWiki;

use MagicWord;
use ParserOutput;

/**
 * @license GNU GPL v2+
 * @since 1.9.3
 *
 * @author mwjames
 */
class MagicWordFinder {

	protected $parserOutput = null;

	/**
	 * @since 1.9.3
	 *
	 * @param ParserOutput|null $parserOutput
	 */
	public function __construct( ParserOutput $parserOutput = null ) {
		$this->parserOutput = $parserOutput;
	}

	/**
	 * @since 1.9.3
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
	 * @since 1.9.3
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
	 * @since 1.9.3
	 *
	 * @param array $words
	 */
	public function setMagicWords( array $words ) {

		if ( $this->hasExtensionData() ) {
			return $this->parserOutput->setExtensionData( 'smwmagicwords', $words );
		}

		return $this->parserOutput->mSMWMagicWords = $words;
	}

	/**
	 * @since 1.9.3
	 *
	 * @return array|null
	 */
	public function getMagicWords() {

		if ( $this->hasExtensionData() ) {
			return $this->parserOutput->getExtensionData( 'smwmagicwords' );
		}

		if ( isset( $this->parserOutput->mSMWMagicWords ) ) {
			return $this->parserOutput->mSMWMagicWords;
		}

		return null;
	}

	/**
	 * FIXME Remove when MW 1.21 becomes mandatory
	 */
	protected function hasExtensionData() {
		return method_exists( $this->parserOutput, 'getExtensionData' );
	}

}
