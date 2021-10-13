<?php

namespace SMW\MediaWiki;

use Collation;
use MediaWiki\MediaWikiServices;

/**
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class Collator {

	/**
	 * Used for armoring.
	 */
	const base64 = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/';
	const base64hex = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz{}';

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
	 * @param string $collationName
	 *
	 * @return Collator
	 */
	public static function singleton( $collationName = '' ) {

		$collationName = $collationName === '' ? $GLOBALS['smwgEntityCollation'] : $collationName;

		if ( !isset( self::$instance[$collationName] ) ) {
			$services = MediaWikiServices::getInstance();
			// BC for MW <= 1.36
			if ( method_exists( $services, 'getCollationFactory' ) ) {
				$collation = $services->getCollationFactory()->makeCollation( $collationName );
			} else {
				$collation = Collation::factory( $collationName );
			}

			self::$instance[$collationName] = new self( $collation, $collationName );
		}

		return self::$instance[$collationName];
	}

	/**
	 * For any uca-* generated sortkey armor the string by converting it
	 * to base64 string to prevent invalid XML/UTF output.
	 *
	 * Compared to classical base64, the characters are here translated in
	 * order to preserve bitwise sort order: comparing armored sort keys
	 * bit-per-bit will give the same result as comparing original sort keys
	 * bit-per-bit.
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

		return rtrim( strtr( base64_encode( $text ), self::base64, self::base64hex ), '=' );
	}

	/**
	 * @since 3.0
	 *
	 * The output could be a binary string, it can be armored with the method `armor`.
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
