<?php

use SMW\Exception\DataItemException;

/**
 * This class implements URI data items.
 *
 * @since 1.6
 *
 * @author Markus Krötzsch
 * @ingroup SMWDataItems
 */
class SMWDIUri extends SMWDataItem {

	/**
	 * URI scheme such as "html" or "mailto".
	 * @var string
	 */
	protected $m_scheme;
	/**
	 * "Hierpart" of the URI (usually some authority and path).
	 * @var string
	 */
	protected $m_hierpart;
	/**
	 * Query part of the URI.
	 * @var string
	 */
	protected $m_query;
	/**
	 * Fragment part of the URI.
	 * @var string
	 */
	protected $m_fragment;

	/**
	 * URI to be defined by its components:
	 *
	 * - scheme (e.g. "html"),
	 * - hierpart following the "scheme:" (e.g. "//username@example.org/path)
	 * - query (e.g. "q=Search+term", and
	 * - fragment (e.g. "section-one").
	 *
	 * The complete URI with these examples would be
	 * http://username@example.org/path?q=Search+term#section-one
	 *
	 *         userinfo    host    port
	 *         ┌─┴────┐ ┌────┴────┐ ┌┴┐
	 * https://john.doe@example.com:123/search?name=ferret#nose
	 * └─┬─┘ └───────┬────────────────┘└──┬──┘└──┬───────┘└┬─┘
	 * scheme     authority             path   query   fragment
	 * ┌─┴┐┌──────────────────────────────┴──────────────┐
	 * urn:oasis:names:specification:docbook:dtd:xml:4.1.2
	 *
	 * @param $scheme string for the scheme
	 * @param $hierpart string for the "hierpart"
	 * @param $query string for the query
	 * @param $fragment string for the fragment
	 *
	 * @todo Implement more validation here.
	 */
	public function __construct( $scheme, $hierpart, $query, $fragment, $strict = true ) {
		if ( $strict && ( ( $scheme === '' ) || ( preg_match( '/[^a-zA-Z]/u', $scheme ) ) ) ) {
			throw new DataItemException( "Illegal URI scheme \"$scheme\"." );
		}
		if ( $strict && $hierpart === '' ) {
			throw new DataItemException( "Illegal URI hierpart \"$hierpart\"." );
		}
		$this->m_scheme   = $scheme;
		$this->m_hierpart = $hierpart;
		$this->m_query    = $query;
		$this->m_fragment = $fragment;
	}

	public function getDIType() {
		return SMWDataItem::TYPE_URI;
	}

	/// @todo This should be changed to the spelling getUri().
	public function getURI() {
		$schemesWithDoubleslesh = [
			'http', 'https', 'ftp'
		];

		$uri = $this->m_scheme . ':'
			. ( in_array( $this->m_scheme, $schemesWithDoubleslesh ) ? '//' : '' )
			. $this->m_hierpart
			. ( $this->m_query ? '?' . $this->m_query : '' )
			. ( $this->m_fragment ? '#' . $this->m_fragment : '' );

		// #1878
		// https://tools.ietf.org/html/rfc3986
		// Normalize spaces to use `_` instead of %20 and so ensure
		// that http://example.org/Foo bar === http://example.org/Foo_bar === http://example.org/Foo%20bar
		return str_replace( [ ' ', '%20'], '_', $uri );
	}

	public function getScheme() {
		return $this->m_scheme;
	}

	public function getHierpart() {
		return $this->m_hierpart;
	}

	public function getQuery() {
		return $this->m_query;
	}

	public function getFragment() {
		return $this->m_fragment;
	}

	/**
	 * @since 1.6
	 *
	 * @return string
	 */
	public function getSortKey() {
		return urldecode( $this->getURI() );
	}

	public function getSerialization() {
		return $this->getURI();
	}

	/**
	 * Create a data item from the provided serialization string and type
	 * ID.
	 * @return SMWDIUri
	 */
	public static function doUnserialize( $serialization ) {

		// try to split "schema:rest"
		$parts = explode( ':', $serialization, 2 );
		$strict = true;

		if ( $serialization !== null && count( $parts ) <= 1 ) {
			throw new DataItemException( "Unserialization failed: the string \"$serialization\" is no valid URI." );
		}

		if ( $serialization === null ) {
			$parts = [ '', 'NO_VALUE' ];
			$strict = false;
		}

		$scheme = $parts[0];

		 // try to split "hier-part?queryfrag"
		$parts = explode( '?', $parts[1], 2 );

		if ( count( $parts ) == 2 ) {
			$hierpart = $parts[0];
			 // try to split "query#frag"
			$parts = explode( '#', $parts[1], 2 );
			$query = $parts[0];
			$fragment = ( count( $parts ) == 2 ) ? $parts[1] : '';
		} else {
			$query = '';
			 // try to split "hier-part#frag"
			$parts = explode( '#', $parts[0], 2 );
			$hierpart = $parts[0];
			$fragment = ( count( $parts ) == 2 ) ? $parts[1] : '';
		}

		$hierpart = ltrim( $hierpart, '/' );

		return new SMWDIUri( $scheme, $hierpart, $query, $fragment, $strict );
	}

	public function equals( SMWDataItem $di ) {
		if ( $di->getDIType() !== SMWDataItem::TYPE_URI ) {
			return false;
		}

		return $di->getURI() === $this->getURI();
	}
}
