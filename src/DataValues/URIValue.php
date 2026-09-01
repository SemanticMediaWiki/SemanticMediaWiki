<?php

namespace SMW\DataValues;

use MediaWiki\Parser\Sanitizer;
use SMW\DataItems\DataItem;
use SMW\DataItems\Uri;
use SMW\Encoder;
use SMW\Exception\DataItemException;
use SMW\Localizer\Message;

define( 'SMW_URI_MODE_EMAIL', 1 );
define( 'SMW_URI_MODE_URI', 3 );
define( 'SMW_URI_MODE_ANNOURI', 4 );
define( 'SMW_URI_MODE_TEL', 5 );

/**
 * This datavalue implements URL/URI/ANNURI/PHONE/EMAIL datavalues suitable for
 * defining the respective types of properties.
 *
 * @author Nikolas Iwan
 * @author Markus Krötzsch
 * @ingroup DataValues
 */
class URIValue extends DataValue {

	/**
	 * The value as returned by getWikitext() and getLongText().
	 */
	protected ?string $m_wikitext = null;
	/**
	 * One of the basic modes of operation for this class (emails, URL,
	 * telephone number URI, ...).
	 */
	private ?int $m_mode = null;

	private bool $showUrlContextInRawFormat = true;

	private array $schemeList;

	public function __construct( $typeid ) {
		parent::__construct( $typeid );
		switch ( $typeid ) {
			case '_ema':
				$this->m_mode = SMW_URI_MODE_EMAIL;
				break;
			case '_anu':
				$this->m_mode = SMW_URI_MODE_ANNOURI;
				break;
			case '_tel':
				$this->m_mode = SMW_URI_MODE_TEL;
				break;
			case '__spu':
			case '_uri':
			case '_url':
			default:
				$this->m_mode = SMW_URI_MODE_URI;
				break;
		}

		$this->schemeList = array_flip( $GLOBALS['smwgURITypeSchemeList'] );
	}

	protected function parseUserValue( $value ): void {
		$value = trim( $value );
		$this->m_wikitext = $value;
		if ( $this->m_caption === false ) {
			$this->m_caption = $this->m_wikitext;
		}

		$scheme = $hierpart = $query = $fragment = '';
		if ( $value === '' ) { // do not accept empty strings
			$this->addErrorMsg( [ 'smw_emptystring' ] );
			return;
		}

		switch ( $this->m_mode ) {
			case SMW_URI_MODE_URI:
			case SMW_URI_MODE_ANNOURI:
				// Whether the url value was externally encoded or not
				if ( strpos( $value, "%" ) === false ) {
					$this->showUrlContextInRawFormat = false;
				}

				$uriParts = $this->splitAndEncodeUri( $value );
				if ( $uriParts == false ) {
					return;
				}
				[ $scheme, $hierpart, $query, $fragment ] = $uriParts;

				break;
			case SMW_URI_MODE_TEL:
				$scheme = 'tel';

				if ( substr( $value, 0, 4 ) === 'tel:' ) { // accept optional "tel"
					$value = substr( $value, 4 );
					$this->m_wikitext = $value;
				}

				$hierpart = preg_replace( '/(?<=[0-9]) (?=[0-9])/', '\1-\2', $value );
				$hierpart = str_replace( ' ', '', $hierpart );
				if ( substr( $hierpart, 0, 2 ) == '00' ) {
					$hierpart = '+' . substr( $hierpart, 2 );
				}

				if ( !$this->getOption( self::OPT_QUERY_CONTEXT ) && ( ( strlen( preg_replace( '/[^0-9]/', '', $hierpart ) ) < 6 ) ||
					( preg_match( '<[-+./][-./]>', $hierpart ) ) ||
					( !self::isValidTelURI( 'tel:' . $hierpart ) ) ) ) {
					// TODO: introduce error-message for "bad" phone number
					$this->addErrorMsg( [ 'smw_baduri', $this->m_wikitext ] );
					return;
				}
				break;
			case SMW_URI_MODE_EMAIL:
				$scheme = 'mailto';
				if ( strpos( $value, 'mailto:' ) === 0 ) { // accept optional "mailto"
					$value = substr( $value, 7 );
					$this->m_wikitext = $value;
				}

				if ( !$this->getOption( self::OPT_QUERY_CONTEXT ) && !Sanitizer::validateEmail( $value ) ) {
					/// TODO: introduce error-message for "bad" email
					$this->addErrorMsg( [ 'smw_baduri', $value ] );
					return;
				}
				$hierpart = $this->encodeUriPart( $value );
		}

		// Now create the URI data item:
		try {
			$this->m_dataitem = new Uri(
				$scheme, $hierpart, $query, $fragment, !$this->getOption( self::OPT_QUERY_CONTEXT )
			);
		} catch ( DataItemException ) {
			$this->addErrorMsg( [ 'smw_baduri', $this->m_wikitext ] );
		}
	}

	/**
	 * Returns true if the argument is a valid RFC 3966 phone number.
	 * Only global phone numbers are supported, and no full validation
	 * of parameters (appended via ;param=value) is performed.
	 */
	protected static function isValidTelURI( $s ): bool {
		$tel_uri_regex = '<^tel:\+[0-9./-]*[0-9][0-9./-]*(;[0-9a-zA-Z-]+=(%[0-9a-zA-Z][0-9a-zA-Z]|[0-9a-zA-Z._~:/?#[\]@!$&\'()*+,;=-])*)*$>';
		return (bool)preg_match( $tel_uri_regex, $s );
	}

	/**
	 * @see DataValue::loadDataItem()
	 */
	protected function loadDataItem( DataItem $dataItem ): bool {
		if ( $dataItem->getDIType() !== DataItem::TYPE_URI ) {
			return false;
		}

		$this->m_dataitem = $dataItem;
		if ( $this->m_mode == SMW_URI_MODE_EMAIL ) {
			$this->m_wikitext = substr( $dataItem->getURI(), 7 );
		} elseif ( $this->m_mode == SMW_URI_MODE_TEL ) {
			$this->m_wikitext = substr( $dataItem->getURI(), 4 );
		} else {
			$this->m_wikitext = $dataItem->getURI();
		}

		$this->m_caption = $this->m_wikitext;
		$this->showUrlContextInRawFormat = false;

		return true;
	}

	public function getShortWikiText( $linked = null ) {
		[ $url, $caption ] = $this->decodeUriContext( $this->m_caption, $linked );

		if ( $linked === null || ( $linked === false ) || ( $url === '' ) ||
			( $this->m_outformat == '-' ) || ( $this->m_caption === '' ) ) {
			return $caption;
		} elseif ( $this->m_outformat == 'nowiki' ) {
			return $this->makeNonlinkedWikiText( $caption );
		} else {
			return '[' . $url . ' ' . $caption . ']';
		}
	}

	public function getShortHTMLText( $linker = null ) {
		[ $url, $caption ] = $this->decodeUriContext( $this->m_caption, $linker );

		if ( $linker === null || ( !$this->isValid() ) || ( $url === '' ) ||
			( $this->m_outformat == '-' ) || ( $this->m_outformat == 'nowiki' ) ||
			( $this->m_caption === '' ) || $linker === false ) {
			return $caption;
		}

		return $linker->makeExternalLink( $url, $caption );
	}

	public function getLongWikiText( $linker = null ) {
		if ( !$this->isValid() ) {
			return $this->getErrorText();
		}

		[ $url, $wikitext ] = $this->decodeUriContext( $this->m_wikitext, $linker );

		if ( $linker === null || $linker === false || $url === '' || $this->m_outformat == '-' ) {
			return $wikitext;
		}

		if ( $this->m_outformat == 'nowiki' ) {
			return $this->makeNonlinkedWikiText( $wikitext );
		}

		return '[' . $url . ' ' . $wikitext . ']';
	}

	public function getLongHTMLText( $linker = null ) {
		if ( !$this->isValid() ) {
			return $this->getErrorText();
		}

		[ $url, $wikitext ] = $this->decodeUriContext( $this->m_wikitext, $linker );

		if ( $linker === null || $linker === false || $url === '' ||
			$this->m_outformat == '-' || $this->m_outformat == 'nowiki' ) {
			return $wikitext;
		}

		return $linker->makeExternalLink( $url, $wikitext );
	}

	public function getWikiValue() {
		return $this->m_wikitext;
	}

	public function getURI(): string {
		return $this->getUriDataitem()->getURI();
	}

	protected function getServiceLinkParams(): array {
		// Create links to mapping services based on a wiki-editable message. The parameters
		// available to the message are:
		// $1: urlencoded version of URI/URL value (includes mailto: for emails)
		return [ rawurlencode( $this->getUriDataitem()->getURI() ) ];
	}

	/**
	 * Get a URL for hyperlinking this URI, or the empty string if this URI
	 * is not hyperlinked in MediaWiki.
	 */
	public function getURL(): string {
		global $wgUrlProtocols;

		foreach ( $wgUrlProtocols as $prot ) {
			if ( ( $prot == $this->getUriDataitem()->getScheme() . ':' ) || ( $prot == $this->getUriDataitem()->getScheme() . '://' ) ) {
				return $this->getUriDataitem()->getURI();
			}
		}

		return '';
	}

	/**
	 * Helper function to get the current dataitem, or some dummy URI
	 * dataitem if the dataitem was not set. This makes it easier to
	 * write code that avoids errors even if the data was not
	 * initialized properly.
	 * @return Uri
	 */
	protected function getUriDataitem() {
		if ( $this->m_dataitem !== null ) {
			return $this->m_dataitem;
		} else { // note: use "noprotocol" to avoid accidental use in an MW link, see getURL()
			return new Uri( 'noprotocol', 'x', '', '', $this->m_typeid );
		}
	}

	/**
	 * Helper function that changes a URL string in such a way that it
	 * can be used in wikitext without being turned into a hyperlink,
	 * while still displaying the same characters. The use of
	 * &lt;nowiki&gt; is avoided, since the resulting strings may be
	 * inserted during parsing, after this has been stripped.
	 *
	 * @since 1.8
	 */
	protected function makeNonlinkedWikiText( $url ): string|array {
		return str_replace( ':', '&#58;', $url );
	}

	private function decodeUriContext( $context, $linker ): array {
		$url = $this->getURL();

		// Decode but skip if context is just the URI
		// rather than a distinct caption.
		if ( $context !== $url && !$this->showUrlContextInRawFormat ) {
			// Prior to decoding, turn any `-` into an internal
			// representation to avoid potential breakage.
			$context = Encoder::decode( str_replace( '-', '-2D', $context ) );
		}

		if ( $context !== $url && $this->m_mode !== SMW_URI_MODE_EMAIL && $linker !== null ) {
			$context = str_replace( '_', ' ', $context ?? '' );
		}

		// Allow the display without `_` so that URIs can be split
		// during the outout by the browser without breaking the URL itself
		// as it contains the `_` for spaces
		return [ $url, $context ];
	}

	/**
	 * Attempts to split URI into its constituent parts
	 * (scheme, hierarchical part, query string and fragment),
	 * and does some encoding.
	 *
	 * @param mixed $value
	 * @return string[]|bool false if it does not pass checks
	 */
	private function splitAndEncodeUri( $value ): array|bool {
		// try to split "scheme:rest"
		$parts = explode( ':', $value, 2 );
		if ( count( $parts ) == 1 ) {
			// possibly add "http" as default
			$value = 'http://' . $value;
			$parts[1] = $parts[0];
			$parts[0] = 'http';
		}

		// Check against blacklist
		if ( !$this->checksOutAgainstUriBlacklist( $value ) ) {
			return false;
		}

		// decompose general URI components
		$scheme = $parts[0];

		if ( !$this->getOption( self::OPT_QUERY_CONTEXT ) && !isset( $this->schemeList[$scheme] ) ) {
			$this->addErrorMsg( [ 'smw-datavalue-uri-invalid-scheme', $scheme ] );
			return false;
		}

		// try to split "hier-part?query#frag"
		$parts = explode( '?', $parts[1], 2 );
		if ( count( $parts ) == 2 ) {
			$hierpart = $parts[0];
			// try to split "query#frag"
			$parts = explode( '#', $parts[1], 2 );
			$query = $parts[0];
			$fragment = ( count( $parts ) == 2 ) ? $parts[1] : '';
		} else {
			// try to split "hier-part#frag"
			$parts = explode( '#', $parts[0], 2 );
			$hierpart = $parts[0];
			$query = "";
			$fragment = ( count( $parts ) == 2 ) ? $parts[1] : '';
		}

		// We do not validate the URI characters (the data item will do this) but we do some escaping:
		// encode most characters, but leave special symbols as given by user:
		$hierpart = $this->encodeUriPart( $hierpart );
		$query = $this->encodeUriPart( $query );
		$fragment = $this->encodeUriPart( $fragment );
		/// NOTE: we do not support raw [ (%5D) and ] (%5E), although they are needed for ldap:// (but rarely in a wiki)
		/// NOTE: "+" gets encoded, as it is interpreted as space by most browsers when part of a URL;
		///       this prevents tel: from working directly, but we have a datatype for this anyway.

		// Remove '//' if hierarchical part starts with it
		if ( substr( $hierpart, 0, 2 ) === '//' ) {
			$hierpart = substr( $hierpart, 2 );
		}

		// #3540
		if ( $hierpart !== '' && $hierpart[0] === '/' ) {
			$this->addErrorMsg( [ 'smw-datavalue-uri-invalid-authority-path-component', $value, $hierpart ] );
			return false;
		}

		return [ $scheme, $hierpart, $query, $fragment ];
	}

	/**
	 * Encodes URI string by rawlencoding it and restoring
	 * most of RFC 3986's gen-delimiters and sub-delimiters.
	 *
	 * Does not check for '?' or '#' in hierpart,
	 * assuming splitting string has taken care of that.
	 *
	 * @param string $str
	 * @return string
	 */
	private function encodeUriPart( string $str ): string {
		// 5/7 gen-delims: ':', '/', '?', '#', '@'
		// We do not support raw [ (%5D) and ] (%5E), although they
		// are needed for ldap:// if rarely in a wiki
		$gendelimSearch = [ '%3A', '%2F', '%3F', '%23', '%40' ];
		$gendelimReplace = [ ':', '/', '?', '#', '@' ];

		// 11/11 sub-delimiters: ! $ & ' ( ) * + , ; =
		// Take care of following encodings:
		// '+': interpreted as space by most browsers when part of a URL
		// (application/x-www-form-urlencoded). This would prevent tel:
		// from working directly, but we have a datatype for this anyway.
		// '$': used in system message vars (e.g. '$1')
		$subdelimSearch = [ '%21', '%24', '%26', '%27', '%28', '%29', '%2A', '%2B', '%2C', '%3B', '%3D', '%25' ];
		$subdelimReplace = [ '!', '$', '&', '\'', '(', ')', '*', '+', ',', ';', '=' ];

		return str_replace(
			// '%' MUST come last
			array_merge( $gendelimSearch, $subdelimSearch, [ '%25' ] ),
			array_merge( $gendelimReplace, $subdelimReplace, [ '%' ] ),
			rawurlencode( $str )
		);
	}

	private function checksOutAgainstUriBlacklist( string $value ): bool {
		// Don't let percent-encoding evade detection
		$value = str_replace( '%2F', '/', $value );

		$uri_blacklist = explode(
			"\n",
			Message::get( 'smw_uri_blacklist', Message::TEXT, Message::CONTENT_LANGUAGE )
		);
		foreach ( $uri_blacklist as $uri ) {
			$uri = trim( $uri );
			if ( $uri !== '' && $uri == mb_substr( $value, 0, mb_strlen( $uri ) ) ) {
				// disallowed URI!
				$this->addErrorMsg( [ 'smw_baduri', $value ] );
				return false;
			}
		}

		return true;
	}

}

/**
 * @deprecated since 7.0.0
 */
class_alias( URIValue::class, 'SMWURIValue' );
