<?php

namespace SMW\Exporter;

/**
 * @license GNU GPL v2+
 * @since 2.2
 *
 * @author Markus Krötzsch
 */
class UriEscaper {

	/**
	 * This function escapes symbols that might be problematic in XML in a uniform
	 * and injective way. It is used to encode URIs.
	 *
	 * @param string
	 *
	 * @return string
	 */
	static public function encode( $uri ) {

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
	static public function decode( $uri ) {

		$uri = str_replace( array( '-3A', '-22', '-23', '-26', '-27', '-2B', '-21', '-' ),
		                    array( ':', '"', '#', '&', "'", '+', '!', '%' ),
		                   $uri );

		$uri = str_replace( '%2D', '-', $uri );

		return $uri;
	}

}
