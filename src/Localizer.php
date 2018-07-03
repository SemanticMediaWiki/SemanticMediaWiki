<?php

namespace SMW;

use SMW\ExtraneousLanguage\ExtraneousLanguage;
use Language;
use Title;

/**
 * @license GNU GPL v2+
 * @since 2.1
 *
 * @author mwjames
 */
class Localizer {

	/**
	 * @var Localizer
	 */
	private static $instance = null;

	/**
	 * @var Language
	 */
	private $contentLanguage = null;

	/**
	 * @since 2.1
	 *
	 * @param Language $contentLanguage
	 */
	public function __construct( Language $contentLanguage ) {
		$this->contentLanguage = $contentLanguage;
	}

	/**
	 * @since 2.1
	 *
	 * @return Localizer
	 */
	public static function getInstance() {

		if ( self::$instance === null ) {
			self::$instance = new self( $GLOBALS['wgContLang'] );
		}

		return self::$instance;
	}

	/**
	 * @since 2.1
	 */
	public static function clear() {
		self::$instance = null;
	}

	/**
	 * @since 2.1
	 *
	 * @return Language
	 */
	public function getContentLanguage() {
		return $this->contentLanguage;
	}

	/**
	 * @since 2.4
	 *
	 * @return Language
	 */
	public function getUserLanguage() {
		return $GLOBALS['wgLang'];
	}

	/**
	 * @note
	 *
	 * 1. If the page content language is availabe use it as preferred language
	 * (as it is clear that the page content was intended to be in a specific
	 * language)
	 * 2. If no page content language was assigned use the global content
	 * language
	 *
	 * General rules:
	 * - Special pages are in the user language
	 * - Display of values (DV) should use the user language if available otherwise
	 * use the content language as fallback
	 * - Storage of values (DI) should always use the content language
	 *
	 * Notes:
	 * - The page content language is the language in which the content of a page is
	 * written in wikitext
	 *
	 * @since 2.4
	 *
	 * @param DIWikiPage|Title|null $title
	 *
	 * @return Language
	 */
	public function getPreferredContentLanguage( $title = null ) {

		$language = '';

		if ( $title instanceof DIWikiPage ) {
			$title = $title->getTitle();
		}

		// If the page language is different from the global content language
		// then we assume that an explicit language object was given otherwise
		// the Title is using the content language as fallback
		if ( $title instanceof Title ) {
			$language = $title->getPageLanguage();
		}

		return $language instanceof Language ? $language : $this->getContentLanguage();
	}

	/**
	 * @since 2.4
	 *
	 * @param string $languageCode
	 *
	 * @return Language
	 */
	public function getLanguage( $languageCode = '' ) {

		if ( $languageCode === '' || !$languageCode || $languageCode === null ) {
			return $this->getContentLanguage();
		}

		return Language::factory( $languageCode );
	}

	/**
	 * @since 2.4
	 *
	 * @param Language|string $languageCode
	 *
	 * @return ExtraneousLanguage
	 */
	public function getExtraneousLanguage( $language = '' ) {

		$languageCode = $language;

		if ( $language instanceof Language ) {
			$languageCode = $language->getCode();
		}

		if ( $languageCode === '' || !$languageCode || $languageCode === null ) {
			$languageCode = $this->getContentLanguage()->getCode();
		}

		return ExtraneousLanguage::getInstance()->fetchByLanguageCode( $languageCode );
	}

	/**
	 * @since 2.1
	 *
	 * @param integer $index
	 *
	 * @return string
	 */
	public function getNamespaceTextById( $index ) {
		return str_replace( '_', ' ', $this->contentLanguage->getNsText( $index ) );
	}

	/**
	 * @since 2.5
	 *
	 * @param integer $index
	 *
	 * @return string
	 */
	public function getCanonicalNamespaceTextById( $index ) {

		$canonicalNames = NamespaceManager::getCanonicalNames();

		if ( isset( $canonicalNames[$index] ) ) {
			return $canonicalNames[$index];
		}

		return \MWNamespace::getCanonicalName( $index );
	}

	/**
	 * @since 2.1
	 *
	 * @param string $namespaceName
	 *
	 * @return integer|boolean
	 */
	public function getNamespaceIndexByName( $namespaceName ) {
		return $this->contentLanguage->getNsIndex( str_replace( ' ', '_', $namespaceName ) );
	}

	/**
	 * @since 2.4
	 *
	 * @param string $languageCode
	 *
	 * @return boolean
	 */
	public static function isKnownLanguageTag( $languageCode ) {

		$languageCode = mb_strtolower( $languageCode );

		// FIXME 1.19 doesn't know Language::isKnownLanguageTag
		if ( !method_exists( '\Language', 'isKnownLanguageTag' ) ) {
			return Language::isValidBuiltInCode( $languageCode );
		}

		return Language::isKnownLanguageTag( $languageCode );
	}

	/**
	 * @see IETF language tag / BCP 47 standards
	 *
	 * @since 2.4
	 *
	 * @param string $languageCode
	 *
	 * @return string
	 */
	public static function asBCP47FormattedLanguageCode( $languageCode ) {
		if ( !is_callable( [ '\LanguageCode', 'bcp47' ] ) ) {
			// Backwards compatibility: remove once MW 1.30 is no
			// longer supported (#3179)
			return wfBCP47( $languageCode );
		}
		return \LanguageCode::bcp47( $languageCode );
	}

	/**
	 * @deprecated 2.5, use Localizer::getAnnotatedLanguageCodeFrom instead
	 * @since 2.4
	 *
	 * @param string &$value
	 *
	 * @return string|false
	 */
	public static function getLanguageCodeFrom( &$value ) {
		return self::getAnnotatedLanguageCodeFrom( $value );
	}

	/**
	 * @since 2.5
	 *
	 * @param integer $index
	 * @param string $text
	 *
	 * @return string
	 */
	public function createTextWithNamespacePrefix( $index, $text ) {
		return $this->getNamespaceTextById( $index ) . ':' . $text;
	}

	/**
	 * @since 2.5
	 *
	 * @param integer $ns
	 * @param string $url
	 *
	 * @return string
	 */
	public function getCanonicalizedUrlByNamespace( $index, $url ) {

		$namespace = $this->getNamespaceTextById( $index );

		return str_replace(
			array(
				wfUrlencode( '/' . $namespace .':' ),
				'/' . $namespace .':'
			),
			'/' . $this->getCanonicalNamespaceTextById( $index ) . ':',
			$url
		);
	}

	/**
	 * @since 2.4
	 *
	 * @param string &$value
	 *
	 * @return string|false
	 */
	public static function getAnnotatedLanguageCodeFrom( &$value ) {

		if ( strpos( $value, '@' ) === false ) {
			return false;
		}

		if ( ( $langCode = mb_substr( strrchr( $value, "@" ), 1 ) ) !== '' ) {
			$value = str_replace( '_', ' ', substr_replace( $value, '', ( mb_strlen( $langCode ) + 1 ) * -1 ) );
		}

		// Do we want to check here whether isKnownLanguageTag or not?
		if ( $langCode !== '' && ctype_alpha( str_replace( array( '-' ), '', $langCode ) ) ) {
			return $langCode;
		}

		return false;
	}

	/**
	 * @see Language::convertDoubleWidth
	 *
	 * Convert double-width roman characters to single-width.
	 * range: ff00-ff5f ~= 0020-007f
	 *
	 * @param string $string
	 *
	 * @return string
	 */
	public static function convertDoubleWidth( $string ) {
		static $full = null;
		static $half = null;

		if ( $full === null ) {
			$fullWidth = "０１２３４５６７８９ＡＢＣＤＥＦＧＨＩＪＫＬＭＮＯＰＱＲＳＴＵＶＷＸＹＺａｂｃｄｅｆｇｈｉｊｋｌｍｎｏｐｑｒｓｔｕｖｗｘｙｚ";
			$halfWidth = "0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz";

			// http://php.net/manual/en/function.str-split.php, mb_str_split
			$length = mb_strlen( $fullWidth, "UTF-8" );
			$full = array();

			for ( $i = 0; $i < $length; $i += 1 ) {
				$full[] = mb_substr( $fullWidth, $i, 1, "UTF-8" );
			}

			$half = str_split( $halfWidth );
		}

		return str_replace( $full, $half, trim( $string ) );
	}

}
