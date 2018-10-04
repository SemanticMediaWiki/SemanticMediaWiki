<?php

namespace SMW\Exporter;

use SMW\DIWikiPage;

/**
 * @license GNU GPL v2+
 * @since 2.2
 *
 * @author Markus Krötzsch
 */
class Escaper {

	/**
	 * @since 2.2
	 *
	 * @param DIWikiPage $diWikiPage
	 *
	 * @return string
	 */
	static public function encodePage( DIWikiPage $diWikiPage ) {

		$localName = '';

		if ( $diWikiPage->getInterwiki() !== '' ) {
			$localName = $diWikiPage->getInterwiki() . ':';
		}

		if ( $diWikiPage->getNamespace() === SMW_NS_PROPERTY ) {
			$localName .= 'Property' . ':' . $diWikiPage->getDBkey();
		} elseif ( $diWikiPage->getNamespace() === NS_CATEGORY ) {
			$localName .= 'Category' . ':' . $diWikiPage->getDBkey();
		} elseif ( $diWikiPage->getNamespace() !== NS_MAIN ) {
			$localName .= str_replace( ' ', '_', $GLOBALS['wgContLang']->getNSText( $diWikiPage->getNamespace() ) ) . ':' . $diWikiPage->getDBkey();
		} else {
			$localName .= $diWikiPage->getDBkey();
		}

		return self::encodeUri( $localName );
	}

	/**
	 * @param string
	 *
	 * @return string
	 */
	static public function armorChars( $string ) {
		return str_replace( [ '/' ], [ '-2F' ], $string );
	}

	/**
	 * This function escapes symbols that might be problematic in XML in a uniform
	 * and injective way.
	 *
	 * @param string
	 *
	 * @return string
	 */
	static public function encodeUri( $uri ) {

		$uri = $GLOBALS['smwgExportResourcesAsIri'] ? $uri : wfUrlencode( $uri );

		$uri = str_replace(
			[ '-', ' ' ],
			[ '-2D', '_' ],
			$uri
		);

		$uri = str_replace(
			[ '*', ',' , ';', '<', '>', '(', ')', '[', ']', '{', '}', '\\', '$', '^', ':', '"', '#', '&', "'", '+', '!', '%' ],
			[ '-2A', '-2C', '-3B', '-3C', '-3E', '-28', '-29', '-5B', '-5D', '-7B', '-7D', '-5C', '-24', '-5E', '-3A', '-22', '-23', '-26', '-27', '-2B', '-21', '-' ],
			$uri
		);

		return $uri;
	}

	/**
	 * This function unescapes URIs generated with Escaper::decodeUri.
	 *
	 * @param string
	 *
	 * @return string
	 */
	static public function decodeUri( $uri ) {

		$uri = str_replace(
			[ '-2A', '-2C', '-3B', '-3C', '-3E', '-28', '-29', '-5B', '-5D', '-7B', '-7D', '-5C', '-24', '-5E', '-3A', '-22', '-23', '-26', '-27', '-2B', '-21', '-25', '-' ],
			[ '*', ',' , ';', '<', '>', '(', ')', '[', ']', '{', '}', '\\', '$', '^', ':', '"', '#', '&', "'", '+', '!', '%', '%' ],
			$uri
		);

		$uri = str_replace( '%2D', '-', $uri );

		return $uri;
	}

}
