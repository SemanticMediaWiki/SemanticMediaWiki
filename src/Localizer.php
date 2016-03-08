<?php

namespace SMW;

use Language;

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
	 * @param Language|null $userLanguage
	 */
	public function __construct( Language $contentLanguage) {
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
	public static function getUserLanguage() {
		return $GLOBALS['wgLang'];
	}

	/**
	 * @since 2.4
	 *
	 * @return Language
	 */
	public static function getExtraneousLanguage() {
		return $GLOBALS['smwgContLang'];
	}

	/**
	 * @since 2.1
	 *
	 * @param integer $namespaceId
	 *
	 * @return string
	 */
	public function getNamespaceTextById( $namespaceId ) {
		return $this->contentLanguage->getNsText( $namespaceId );
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
	public static function isSupportedLanguage( $languageCode ) {

		$languageCode = mb_strtolower( $languageCode );

		// FIXME 1.19 doesn't know Language::isSupportedLanguage
		if ( !method_exists( '\Language', 'isSupportedLanguage' ) ) {
			return Language::isValidBuiltInCode( $languageCode );
		}

		return Language::isSupportedLanguage( $languageCode );
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
		return wfBCP47( $languageCode );
	}

}
