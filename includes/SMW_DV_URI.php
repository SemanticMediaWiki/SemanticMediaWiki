<?php
/**
 * @file
 * @ingroup SMWDataValues
 */

define( 'SMW_URI_MODE_EMAIL', 1 );
define( 'SMW_URI_MODE_URI', 3 );
define( 'SMW_URI_MODE_ANNOURI', 4 );
define( 'SMW_URI_MODE_TEL', 5 );

/**
 * This datavalue implements URL/URI/ANNURI/PHONE/EMAIL-Datavalues suitable for defining
 * the respective types of properties.
 *
 * @author Nikolas Iwan
 * @author Markus Krötzsch
 * @ingroup SMWDataValues
 * @bug Correctly create safe HTML and Wiki text.
 */
class SMWURIValue extends SMWDataValue {

	/// Value; usually a human readable version of the URI (esp. "mailto:" might be ommitted)
	private $m_value = '';
	/// Only set if a link should be created in the wiki.
	private $m_url = '';
	/// Canonical URI for identifying the object
	private $m_uri = '';
	/// Distinguish different modes (emails, URL, ...)
	private $m_mode = '';

	public function SMWURIValue( $typeid ) {
		SMWDataValue::__construct( $typeid );
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
			case '_uri': case '_url': case '__spu': default:
				$this->m_mode = SMW_URI_MODE_URI;
				break;
		}
	}

	protected function parseUserValue( $value ) {
		smwfLoadExtensionMessages( 'SemanticMediaWiki' );
		$value = trim( $value );
		$this->m_url = '';
		$this->m_uri = '';
		$this->m_value = $value;
		if ( $this->m_caption === false ) {
			$this->m_caption = $this->m_value;
		}
		if ( $value != '' ) { // do not accept empty strings
			switch ( $this->m_mode ) {
				case SMW_URI_MODE_URI: case SMW_URI_MODE_ANNOURI:
					$parts = explode( ':', $value, 2 ); // try to split "schema:rest"
					if ( count( $parts ) == 1 ) { // take "http" as default
						$value = 'http://' . $value;
						$parts[1] = $parts[0];
						$parts[0] = 'http';
					} elseif ( ( count( $parts ) < 1 ) || ( $parts[0] == '' ) || ( $parts[1] == '' ) || ( preg_match( '/[^a-zA-Z]/u', $parts[0] ) ) ) {
						$this->addError( wfMsgForContent( 'smw_baduri', $value ) );
						return true;
					}

					// check against blacklist
					$uri_blacklist = explode( "\n", wfMsgForContent( 'smw_uri_blacklist' ) );
					foreach ( $uri_blacklist as $uri ) {
						$uri = trim( $uri );
						if ( $uri == mb_substr( $value, 0, mb_strlen( $uri ) ) ) { // disallowed URI!
							$this->addError( wfMsgForContent( 'smw_baduri', $uri ) );
							return true;
						}
					}
					// simple check for invalid characters: ' ', '{', '}'
// 					$check1 = "@(\}|\{| )+@u";
// 					if (preg_match($check1, $value, $matches)) {
// 						$this->addError(wfMsgForContent('smw_baduri', $value));
// 						break;
// 					}
/// TODO: the remaining checks need improvement
// 					// validate last part of URI (after #) if provided
// 					$uri_ex = explode('#',$value);
// 					$check2 = "@^[a-zA-Z0-9-_\%]+$@u"; ///FIXME: why only ascii symbols?
// 					if(sizeof($uri_ex)>2 ){ // URI should only contain at most one '#'
// 						$this->addError(wfMsgForContent('smw_baduri', $value) . 'Debug3');
// 						break;
// 					} elseif ( (sizeof($uri_ex) == 2) && !(preg_match($check2, $uri_ex[1])) ) {
// 						$this->addError(wfMsgForContent('smw_baduri', $value) . 'Debug4');
// 						break;
// 					}
// 					// validate protocol + domain part of URI
//// 					$check3 = "@^([a-zA-Z]{0,6}:)[a-zA-Z0-9\.\/%]+$@";  //simple regexp for protocol+domain part of URI
// 					$check3 = "@^([a-zA-Z]:)[a-zA-Z0-9\.\/%]+$@";  //simple regexp for protocol+domain part of URI
// 					/// FIXME: why {0,6}?
// 					if (!preg_match($check3, $uri_ex[0],$matches)){
// 						$this->addError(wfMsgForContent('smw_baduri', $value) . 'Debug5');
// 						break;
// 					}

					// encode most characters, but leave special symbols as given by user:
					$this->m_uri = str_replace( array( '%3A', '%2F', '%23', '%40', '%3F', '%3D', '%26', '%25' ), array( ':', '/', '#', '@', '?', '=', '&', '%' ), rawurlencode( $value ) );
					/// NOTE: we do not support raw [ (%5D) and ] (%5E), although they are needed for ldap:// (but rarely in a wiki)
					/// NOTE: we do not check the validity of the use of the raw symbols -- does RFC 3986 as such care?
					/// NOTE: "+" gets encoded, as it is interpreted as space by most browsers when part of a URL;
					///       this prevents tel: from working directly, but we should have a datatype for this anyway.
					global $wgUrlProtocols;
					foreach ( $wgUrlProtocols as $prot ) { // only set URL if wiki-enabled protocol
						if ( ( $prot == $parts[0] . ':' ) || ( $prot == $parts[0] . '://' ) ) {
							$this->m_url = $this->m_uri;
							break;
						}
					}
					break;
				case SMW_URI_MODE_TEL:
					if ( substr( $value, 0, 4 ) === 'tel:' ) {
						$value = substr( $value, 4 );
						$this->m_value = $value;
					}
					$value = preg_replace( '/(?<=[0-9]) (?=[0-9])/', '\1-\2', $value );
					$value = str_replace( ' ', '', $value );
					if ( substr( $value, 0, 2 ) == '00' ) {
						$value = '+' . substr( $value, 2 );
					}
					$value = 'tel:' . $value;
					if ( ( strlen( preg_replace( '/[^0-9]/', '', $value ) ) < 6 ) ||
						 ( preg_match( '<[-+./][-./]>', $value ) ) ||
						 ( !SMWURIValue::isValidTelURI( $value ) ) ) { ///TODO: introduce error-message for "bad" phone number
						 $this->addError( wfMsgForContent( 'smw_baduri', $this->m_value ) );
					}
					$this->m_uri = $value;
					break;
				case SMW_URI_MODE_EMAIL:
					if ( strpos( $value, 'mailto:' ) === 0 ) { // accept optional "mailto"
						$value = substr( $value, 7 );
						$this->m_value = $value;
					}
					$check = "#^([_a-zA-Z0-9-]+)((\.[_a-zA-Z0-9-]+)*)@([_a-zA-Z0-9-]+(\.[_a-zA-Z0-9-]+)*)\.([a-zA-Z]{2,6})$#u";
					if ( !preg_match( $check, $value ) ) {
						///TODO: introduce error-message for "bad" email
						$this->addError( wfMsgForContent( 'smw_baduri', $value ) );
						break;
					}
					$this->m_url = 'mailto:' . str_replace( array( '%3A', '%2F', '%23', '%40', '%3F', '%3D', '%26', '%25' ), array( ':', '/', '#', '@', '?', '=', '&', '%' ), rawurlencode( $value ) );
					$this->m_uri = $this->m_url;
			}
		} else {
			$this->addError( wfMsgForContent( 'smw_emptystring' ) );
		}

		return true;
	}

	/**
	 * Returns true if the argument is a valid RFC 3966 phone number.
	 * Only global phone numbers are supported, and no full validation
	 * of parameters (appended via ;param=value) is performed.
	 */
	protected static function isValidTelURI( $s ) {
		$tel_uri_regex = '<^tel:\+[0-9./-]*[0-9][0-9./-]*(;[0-9a-zA-Z-]+=(%[0-9a-zA-Z][0-9a-zA-Z]|[0-9a-zA-Z._~:/?#[\]@!$&\'()*+,;=-])*)*$>';
		return (bool) preg_match( $tel_uri_regex, $s );
	}


	protected function parseDBkeys( $args ) {
		$this->m_uri = $args[0];
		$this->m_value = $this->m_uri;
		$this->m_caption = $this->m_value;
		if ( $this->m_mode == SMW_URI_MODE_EMAIL ) {
			$this->m_url = $this->m_value;
			if ( strpos( $this->m_uri, 'mailto:' ) === 0 ) { // catch inconsistencies in DB, should usually be the case
				$this->m_caption = substr( $this->m_value, 7 );
				$this->m_value = $this->m_caption;
			} else { // this case is only for backwards compatibility/repair; may vanish at some point
				$this->m_uri = 'mailto:' . $this->m_value;
				$this->m_url = $this->m_uri;
			}
		} elseif ( $this->m_mode == SMW_URI_MODE_TEL ) {
			$this->m_url = '';
			if ( strpos( $this->m_uri, 'tel:' ) === 0 ) { // catch inconsistencies in DB, should usually be the case
				$this->m_caption = substr( $this->m_value, 4 );
				$this->m_value = $this->m_caption;
			}
		} else {
			$parts = explode( ':', $this->m_uri, 2 ); // try to split "schema:rest"
			global $wgUrlProtocols;
			$this->m_url = '';
			foreach ( $wgUrlProtocols as $prot ) { // only set URL if wiki-enabled protocol
				if ( ( $prot == $parts[0] . ':' ) || ( $prot == $parts[0] . '://' ) ) {
					$this->m_url = $this->m_uri;
					break;
				}
			}
		}
	}

	public function getShortWikiText( $linked = null ) {
		$this->unstub();
		if ( ( $linked === null ) || ( $linked === false ) || ( $this->m_outformat == '-' ) || ( $this->m_url == '' ) || ( $this->m_caption == '' ) ) {
			return $this->m_caption;
		} else {
			return '[' . $this->m_url . ' ' . $this->m_caption . ']';
		}
	}

	public function getShortHTMLText( $linker = null ) {
		$this->unstub();
		if ( ( $linker === null ) || ( !$this->isValid() ) || ( $this->m_outformat == '-' ) || ( $this->m_url == '' ) || ( $this->m_caption == '' ) ) {
			return $this->m_caption;
		} else {
			return $linker->makeExternalLink( $this->m_url, $this->m_caption );
		}
	}

	public function getLongWikiText( $linked = null ) {
		if ( !$this->isValid() ) {
			return $this->getErrorText();
		}
		if ( ( $linked === null ) || ( $linked === false ) || ( $this->m_outformat == '-' ) || ( $this->m_url == '' ) ) {
			return $this->m_value;
		} else {
			return '[' . $this->m_url . ' ' . $this->m_value . ']';
		}
	}

	public function getLongHTMLText( $linker = null ) {
		if ( !$this->isValid() ) {
			return $this->getErrorText();
		}
		if ( ( $linker === null ) || ( $this->m_outformat == '-' ) || ( $this->m_url == '' ) ) {
			return htmlspecialchars( $this->m_value );
		} else {
			return $linker->makeExternalLink( $this->m_url, $this->m_value );
		}
	}

	public function getDBkeys() {
		$this->unstub();
		return array( $this->m_uri );
	}

	public function getSignature() {
		return 't';
	}

	public function getValueIndex() {
		return 0;
	}

	public function getLabelIndex() {
		return 0;
	}

	public function getWikiValue() {
		$this->unstub();
		return $this->m_value;
	}

	protected function getServiceLinkParams() {
		$this->unstub();
		// Create links to mapping services based on a wiki-editable message. The parameters
		// available to the message are:
		// $1: urlencoded version of URI/URL value (includes mailto: for emails)
		return array( rawurlencode( $this->m_uri ) );
	}

	public function getExportData() {
		if ( $this->isValid() ) {
			$res = new SMWExpResource( str_replace( '&', '&amp;', $this->m_uri ), $this );
			return new SMWExpData( $res );
		} else {
			return null;
		}
	}

}

