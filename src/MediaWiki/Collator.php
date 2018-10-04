<?php

namespace SMW\MediaWiki;

use Collation;

/**
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class Collator {

	/**
	 * @var Collator
	 */
	private static $instance = [];

	/**
	 * @var Collation
	 */
	private $collation;

	/**
	 * @var string
	 */
	private $collationName;

	/**
	 * @private
	 *
	 * @since 3.0
	 *
	 * @param Collation $collation
	 * @param string $collationName
	 */
	public function __construct( Collation $collation, $collationName = '' ) {
		$this->collation = $collation;
		$this->collationName = $collationName;
	}

	/**
	 * @since 3.0
	 *
	 * @param srtring $collationName
	 *
	 * @return Collator
	 */
	public static function singleton( $collationName = '' ) {

		$collationName = $collationName === '' ? $GLOBALS['smwgEntityCollation'] : $collationName;

		if ( !isset( self::$instance[$collationName] ) ) {
			self::$instance[$collationName] = new self( Collation::factory( $collationName ), $collationName );
		}

		return self::$instance[$collationName];
	}

	/**
	 * For any uca-* generated sortkey armor any invalid or unrecognized UTF-8
	 * characters to prevent an invalid XML/UTF output.
	 *
	 * Characters that cannot be expressed are replaced by ? which is surely
	 * inaccurate in comparison to the original uca-* sortkey but it allows to
	 * replicate a near surrogate string to a back-end that requires XML
	 * compliance (triple store).
	 *
	 * @since 3.0
	 *
	 * @param string $text
	 *
	 * @return string
	 */
	public function armor( $text, $source = '' ) {

		if ( strpos( $this->collationName, 'uca' ) === false ) {
			return $text;
		}

		//	$text = mb_convert_encoding( $text, 'UTF-8' );

		// https://magp.ie/2011/01/06/remove-non-utf8-characters-from-string-with-php/
		// Remove all none utf-8 symbols
		$text = str_replace( '�', '', htmlspecialchars( $text, ENT_SUBSTITUTE, 'UTF-8' ) );

		// remove non-breaking spaces and other non-standard spaces
		$text = preg_replace( '~\s+~u', '?', $text );

		// replace controls symbols with "?"
		$text = preg_replace( '~\p{C}+~u', '?', $text );

		return $text;
	}

	/**
	 * @since 3.0
	 *
	 * @param string $text
	 *
	 * @return string
	 */
	public function getSortKey( $text ) {
		return $this->collation->getSortKey( $text );
	}

	/**
	 * @since 3.0
	 *
	 * @param string $text
	 *
	 * @return string
	 */
	public function getFirstLetter( $text ) {

		// Add check otherwise the Collation instance returns with a
		// "Uninitialized string offset: 0"
		if ( $text === '' ) {
			return '';
		}

		return $this->collation->getFirstLetter( $text );
	}

	/**
	 * @since 3.0
	 *
	 * @param string $old
	 * @param string $new
	 *
	 * @return boolean
	 */
	public function isIdentical( $old, $new ) {
		return $this->collation->getSortKey( $old ) === $this->collation->getSortKey( $new );
	}

}
