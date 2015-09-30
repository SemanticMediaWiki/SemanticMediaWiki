<?php

namespace SMW\MediaWiki;

use Language;

/**
 * @license GNU GPL v2+
 * @since 2.2
 *
 * @author mwjames
 */
class ByLanguageCollationMapper {

	/**
	 * @var ByLanguageCollationMapper
	 */
	private static $instance = null;

	/**
	 * @var string
	 */
	private $categoryCollation = null;

	/**
	 * @var Language
	 */
	private $language = null;

	/**
	 * @since 2.2
	 *
	 * @param string $categoryCollation
	 */
	public function __construct( $categoryCollation ) {
		$this->categoryCollation = $categoryCollation;
	}

	/**
	 * @since 2.2
	 *
	 * @return CollationFormatter
	 */
	public static function getInstance() {

		if ( self::$instance === null ) {
			self::$instance = new self( $GLOBALS['wgCategoryCollation'] );
		}

		return self::$instance;
	}

	/**
	 * @since 2.2
	 */
	public static function clear() {
		self::$instance = null;
	}

	/**
	 * @since 2.2
	 *
	 * @return string
	 */
	public function findFirstLetterForCategory( $string ) {

		// Currently MW's `uca-default`, `xx-uca-ckb`, `xx-uca-et` are
		// not supported

		switch ( $this->categoryCollation ) {
			case 'uppercase':
				return $this->formatByUppercaseCollation( $string );
			case 'identity':
			default:
				return $this->formatByIdentityCollation( $string );
		}
	}

	private function formatByUppercaseCollation( $string ) {

		// Use the generic UTF-8 uppercase function
		if ( $this->language === null ) {
			$this->language = Language::factory( 'en' );
		}

		// Note sure what this is for, for details see Collation.php
		if ( isset( $string[0] ) && $string[0] == "\0" ) {
			$string = mb_substr( $string, 0, 1, 'UTF-8' );
		}

		return $this->language->ucfirst( $this->language->firstChar( $string ) );
	}

	private function formatByIdentityCollation( $string ) {

		if ( $this->language === null ) {
			$this->language = $GLOBALS['wgContLang'];
		}

		// Note sure what this is for, for details see Collation.php
		if ( isset( $string[0] ) && $string[0] == "\0" ) {
			$string =  mb_substr( $string, 0, 1, 'UTF-8' );
		}

		return $this->language->firstChar( $string );
	}

}
