<?php

namespace Onoi\Tesa\LanguageDetector;

use TextCat;

/**
 * @license GNU GPL v2+
 * @since 0.1
 *
 * @author mwjames
 */
class TextCatLanguageDetector implements LanguageDetector {

	const VERION = '0.1';

	/**
	 * @var TextCat
	 */
	private $textCat;

	/**
	 * @var array|null
	 */
	private $languageCandidates = null;

	/**
	 * @since 0.1
	 *
	 * @param TextCat|null $textCat
	 */
	public function __construct( TextCat $textCat = null ) {
		$this->textCat = $textCat;

		if ( $this->textCat === null ) {
			$this->textCat = new TextCat();
		}
	}

	/**
	 * @since 0.1
	 *
	 * @param array $languageCandidates
	 */
	public function setLanguageCandidates( array $languageCandidates ) {
		$this->languageCandidates = $languageCandidates;
	}

	/**
	 * @since 0.1
	 *
	 * @param string $text
	 *
	 * @return string|null
	 */
	public function detect( $text ) {

		$languages = $this->textCat->classify( $text, $this->languageCandidates );
		reset( $languages );

		// For now, only return the best match
		return key( $languages );
	}

}
