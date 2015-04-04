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

		if ( $diWikiPage->getNamespace() !== 0 ) {
			$localName .= str_replace( ' ', '_', $GLOBALS['wgContLang']->getNSText( $diWikiPage->getNamespace() ) ) . ':' . $diWikiPage->getDBkey();
		} else {
			$localName .= $diWikiPage->getDBkey();
		}

		return self::encodeUri( wfUrlencode( $localName ) );
	}

	/**
	 * This function escapes symbols that might be problematic in XML in a uniform
	 * and injective way. It is used to encode URIs.
	 *
	 * @param string
	 *
	 * @return string
	 */
	static public function encodeUri( $uri ) {

		$uri = str_replace( '-', '-2D', $uri );

		// $uri = str_replace( '_', '-5F', $uri); //not necessary
		$uri = str_replace( array( ':', '"', '#', '&', "'", '+', '!', '%' ),
		                    array( '-3A', '-22', '-23', '-26', '-27', '-2B', '-21', '-' ),
		                    $uri );

		return $uri;
	}

	/**
	 * This function unescapes URIs generated with SMWExporter::getInstance()->encodeURI. This
	 * allows services that receive a URI to extract e.g. the according wiki page.
	 *
	 * @param string
	 *
	 * @return string
	 */
	static public function decodeUri( $uri ) {

		$uri = str_replace( array( '-3A', '-22', '-23', '-26', '-27', '-2B', '-21', '-' ),
		                    array( ':', '"', '#', '&', "'", '+', '!', '%' ),
		                   $uri );

		$uri = str_replace( '%2D', '-', $uri );

		return $uri;
	}

}
