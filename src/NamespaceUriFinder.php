<?php

namespace SMW;

/**
 * @license GNU GPL v2+
 * @since 2.4
 *
 * @author mwjames
 */
class NamespaceUriFinder {

	/**
	 * @var array
	 */
	private static $namespaceUriList = [
		'owl'   => 'http://www.w3.org/2002/07/owl#',
		'rdf'   => 'http://www.w3.org/1999/02/22-rdf-syntax-ns#',
		'rdfs'  => 'http://www.w3.org/2000/01/rdf-schema#',
		'swivt' => 'http://semantic-mediawiki.org/swivt/1.0#',
		'xsd'   => 'http://www.w3.org/2001/XMLSchema#',
		'skos'  => 'http://www.w3.org/2004/02/skos/core#',
		'foaf'  => 'http://xmlns.com/foaf/0.1/',
		'dc'    => 'http://purl.org/dc/elements/1.1/'
	];

	/**
	 * @since 2.4
	 *
	 * @param string $key
	 *
	 * @return false|string
	 */
	public static function getUri( $key ) {

		$key = strtolower( $key );

		if ( isset( self::$namespaceUriList[$key] ) ) {
			return self::$namespaceUriList[$key];
		}

		return false;
	}

}
