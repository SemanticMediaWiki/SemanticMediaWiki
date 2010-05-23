<?php
/**
 * This file contains the SMWInfolink class.
 *
 * @author Markus Krötzsch
 * @author Jeroen De Dauw
 * 
 * @file
 * @ingroup SMW
 */

/**
 * This class mainly is a container to store URLs for the factbox in a
 * clean way. The class provides methods for creating source code for
 * realising them in wiki or html contexts.
 *
 * @ingroup SMW
 */
class SMWInfolink {
	
	/**
	 * The actual link target.
	 * 
	 * @var string
	 */
	protected $mTarget; 
	
	/**
	 * The label for the link.
	 * 
	 * @var string
	 */
	protected $mCaption;
	
	/**
	 * CSS class of a span to embedd the link into,
	 * or false if no extra style is required.
	 * 
	 * @var mixed
	 */
	protected $mStyle;

	/**
	 * Indicates whether $target is a page name (true) or URL (false).
	 * 
	 * @var boolean
	 */
	protected $mInternal;
	
	/**
	 * Array of parameters, format $name => $value, if any.
	 * 
	 * @var array
	 */
	protected $mParams;

	/**
	 * Create a new link to some internal page or to some external URL.
	 * 
	 * @param boolean $internal Indicates whether $target is a page name (true) or URL (false).
	 * @param string $caption The label for the link.
	 * @param string $target The actual link target.
	 * @param mixed $style CSS class of a span to embedd the link into, or false if no extra style is required.
	 * @param array $params Array of parameters, format $name => $value, if any.
	 */
	public function __construct( $internal, $caption, $target, $style = false, array $params = array() ) {
		$this->mInternal = $internal;
		$this->mCaption = $caption;
		$this->mTarget = $target;
		$this->mStyle = $style;
		$this->mParams = $params;
	}

	/**
	 * Create a new link to an internal page $target.
	 * All parameters are mere strings as used by wiki users.
	 * 
	 * @param string $caption The label for the link.
	 * @param string $target The actual link target.
	 * @param mixed $style CSS class of a span to embedd the link into, or false if no extra style is required.
	 * @param array $params Array of parameters, format $name => $value, if any.
	 * 
	 * @return SMWInfolink
	 */
	public static function newInternalLink( $caption, $target, $style = false, array $params = array() ) {
		return new SMWInfolink( true, $caption, $target, $style, $params );
	}

	/**
	 * Create a new link to an external location $url.
	 * 
	 * @param string $caption The label for the link.
	 * @param string $url The actual link target.
	 * @param mixed $style CSS class of a span to embedd the link into, or false if no extra style is required.
	 * @param array $params Array of parameters, format $name => $value, if any.
	 * 
	 * @return SMWInfolink
	 */
	public static function newExternalLink( $caption, $url, $style = false, array $params = array() ) {
		return new SMWInfolink( false, $caption, $url, $style, $params );
	}

	/**
	 * Static function to construct links to property searches.
	 * 
	 * @param string $caption The label for the link.
	 * @param string $propertyName
	 * @param string $propertyValue
	 * @param mixed $style CSS class of a span to embedd the link into, or false if no extra style is required.
	 * 
	 * @return SMWInfolink
	 */
	public static function newPropertySearchLink( $caption, $propertyName, $propertyValue, $style = 'smwsearch' ) {
		global $wgContLang;
		return new SMWInfolink( true, $caption, $wgContLang->getNsText( NS_SPECIAL ) . ':SearchByProperty', $style, array( $propertyName, $propertyValue ) );
	}

	/**
	 * Static function to construct links to inverse property searches.
	 * 
	 * @param string $caption The label for the link.
	 * @param string $subject
	 * @param string $propertyName
	 * @param mixed $style CSS class of a span to embedd the link into, or false if no extra style is required.
	 * 
	 * @return SMWInfolink
	 */	
	public static function newInversePropertySearchLink( $caption, $subject, $propertyname, $style = false ) {
		global $wgContLang;
		return new SMWInfolink( true, $caption, $wgContLang->getNsText( NS_SPECIAL ) . ':PageProperty/' .  $subject . '::' . $propertyName, $style );
	}

	/**
	 * Static function to construct links to the browsing special.
	 * 
	 * @param string $caption The label for the link.
	 * @param string $titleText
	 * @param mixed $style CSS class of a span to embedd the link into, or false if no extra style is required.
	 * 
	 * @return SMWInfolink
	 */
	public static function newBrowsingLink( $caption, $titleText, $style = 'smwbrowse' ) {
		global $wgContLang;
		return new SMWInfolink( true, $caption, $wgContLang->getNsText( NS_SPECIAL ) . ':Browse', $style, array( $titleText ) );
	}

	/**
	 * Set (or add) parameter values for an existing link.
	 * 
	 * @param mixed $value
	 * @param mixed $key
	 */
	public function setParameter( $value, $key = false ) {
		if ( $key === false ) {
			$this->mParams[] = $value;
		} else {
			$this->mParams[$key] = $value;
		}
	}

	/**
	 * Get the value of some named parameter, or null if no parameter of
	 * that name exists.
	 */
	public function getParameter( $key ) {
		if ( array_key_exists( $key, $this->mParams ) ) {
			return $this->mParams[$key];
		} else {
			return null;
		}
	}

	/**
	 * Change the link text.
	 */
	public function setCaption( $caption ) {
		$this->mCaption = $caption;
	}

	/**
	 * Change the link's CSS class.
	 */
	public function setStyle( $style ) {
		$this->mStyle = $style;
	}

	/**
	 * Returns a suitable text string for displaying this link in HTML or wiki, depending
	 * on whether $outputformat is SMW_OUTPUT_WIKI or SMW_OUTPUT_HTML.
	 *
	 * The parameter $linker controls linking of values such as titles and should
	 * be some Linker object (for HTML output). Some default linker will be created
	 * if needed and not provided.
	 */
	public function getText( $outputformat, $linker = null ) {
		if ( $this->mStyle !== false ) {
			SMWOutputs::requireHeadItem( SMW_HEADER_STYLE ); // make SMW styles available
			$start = "<span class=\"$this->mStyle\">";
			$end = '</span>';
		} else {
			$start = '';
			$end = '';
		}
		
		if ( $this->mInternal ) {
			if ( count( $this->mParams ) > 0 ) {
				$titletext = $this->mTarget . '/' . SMWInfolink::encodeParameters( $this->mParams );
			} else {
				$titletext = $this->mTarget;
			}
			
			$title = Title::newFromText( $titletext );
			
			if ( $title !== null ) {
				if ( $outputformat == SMW_OUTPUT_WIKI ) {
					$link = "[[$titletext|$this->mCaption]]";
				} else { // SMW_OUTPUT_HTML, SMW_OUTPUT_FILE
					$link = $this->getLinker( $linker )->makeKnownLinkObj( $title, $this->mCaption );
				}
			} else { // Title creation failed, maybe illegal symbols or too long; make a direct URL link
			         // (only possible if offending target parts belong to some parameter
			         //  that can be separated from title text,
			         //  e.g. as in Special:Bla/il<leg>al -> Special:Bla&p=il&lt;leg&gt;al)
				$title = Title::newFromText( $this->mTarget );
				
				if ( $title !== null ) {
					if ( $outputformat == SMW_OUTPUT_WIKI ) {
						$link = "[" . $title->getFullURL( SMWInfolink::encodeParameters( $this->mParams, false ) ) . " $this->mCaption]";
					} else { // SMW_OUTPUT_HTML, SMW_OUTPUT_FILE
						$link = $this->getLinker( $linker )->makeKnownLinkObj( $title, $this->mCaption, SMWInfolink::encodeParameters( $this->mParams, false ) );
					}
				} else {
					return ''; // the title was bad, normally this would indicate a software bug
				}
			}
		} else {
			$target = $this->getURL();
			
			if ( $outputformat == SMW_OUTPUT_WIKI ) {
				$link = "[$target $this->mCaption]";
			} else { // SMW_OUTPUT_HTML, SMW_OUTPUT_FILE
				$link = "<a href=\"" . htmlspecialchars( $target ) . "\">$this->mCaption</a>";
			}
		}

		return $start . $link . $end;
	}

	/**
	 * Return hyperlink for this infolink in HTML format.
	 * 
	 * @return string
	 */
	public function getHTML( $linker = null ) {
		return $this->getText( SMW_OUTPUT_HTML, $linker );
	}

	/**
	 * Return hyperlink for this infolink in wiki format.
	 * 
	 * @return string
	 */
	public function getWikiText( $linker = null ) {
		return $this->getText( SMW_OUTPUT_WIKI, $linker );
	}

	/**
	 * Return a fully qualified URL that points to the link target (whether internal or not).
	 * This function might be used when the URL is needed outside normal links, e.g. in the HTML
	 * header or in some metadata file. For making normal links, getText() should be used.
	 * 
	 * @return string
	 */
	public function getURL() {
		if ( $this->mInternal ) {
			$title = Title::newFromText( $this->mTarget );
			
			if ( $title !== null ) {
				return $title->getFullURL( SMWInfolink::encodeParameters( $this->mParams, false ) );
			} else {
				return ''; // the title was bad, normally this would indicate a software bug
			}
		} else {
			if ( count( $this->mParams ) > 0 ) {
				if ( strpos( SMWExporter::expandURI( '&wikiurl;' ), '?' ) === false ) {
					$target = $this->mTarget . '?' . SMWInfolink::encodeParameters( $this->mParams, false );
				} else {
					$target = $this->mTarget . '&' . SMWInfolink::encodeParameters( $this->mParams, false );
				}
			} else {
				$target = $this->mTarget;
			}
			
			return $target;
		}
	}


	/**
	 * Return a Linker object, using the parameter $linker if not null, and creatng a new one
	 * otherwise. $linker is usually a user skin object, while the fallback linker object is
	 * not customised to user settings.
	 * 
	 * @return Linker
	 */
	protected function getLinker( &$linker = null ) {
		if ( $linker === null ) $linker = new Linker();
		return $linker;
	}

	/**
	 * Encode an array of parameters, formatted as $name => $value, to a parameter
	 * string that can be used for linking. If $forTitle is true (default), then the
	 * parameters are encoded for use in a MediaWiki page title (useful for making
	 * internal links to parameterised special pages), otherwise the parameters are
	 * encoded HTTP GET style. The parameter name "x" is used to collect parameters
	 * that do not have any string keys in GET, and hence "x" should never be used
	 * as a parameter name.
	 *
	 * The function SMWInfolink::decodeParameters() can be used to undo this encoding.
	 * It is strongly recommended to not create any code that depends on the concrete
	 * way of how parameters are encoded within this function, and to always use the
	 * respective encoding/decoding methods instead.
	 * 
	 * @param array $params
	 * @param boolean $forTitle
	 */
	static public function encodeParameters( array $params, $forTitle = true ) {
		$result = '';
		
		if ( $forTitle ) {
			foreach ( $params as $name => $value ) {
				if ( is_string( $name ) && ( $name != '' ) ) $value = $name . '=' . $value;
				// Escape certain problematic values. Use SMW-escape
				// (like URLencode but - instead of % to prevent double encoding by later MW actions)
				//
				/// : SMW's parameter separator, must not occur within params
				// - : used in SMW-encoding strings, needs escaping too
				// [ ] < > &lt; &gt; '' |: problematic in MW titles
				// & : sometimes problematic in MW titles ([[&amp;]] is OK, [[&test]] is OK, [[&test;]] is not OK)
				//     (Note: '&' in strings obtained during parsing already has &entities; replaced by
				//      UTF8 anyway)
				// ' ': are equivalent with '_' in MW titles, but are not equivalent in certain parameter values
				// "\n": real breaks not possible in [[...]]
				// "#": has special meaning in URLs, triggers additional MW escapes (using . for %)
				// '%': must be escaped to prevent any impact of double decoding when replacing -
				//      by % before urldecode
				// '?': if not escaped, strange effects were observed on some sites (printout and other
				//      parameters ignored without obvious cause); SMW-escaping is always save to do -- it just
				//      make URLs less readable
				//
				$value = str_replace(
					array( '-', '#', "\n", ' ', '/', '[', ']', '<', '>', '&lt;', '&gt;', '&amp;', '\'\'', '|', '&', '%', '?' ),
					array( '-2D', '-23', '-0A', '-20', '-2F', '-5B', '-5D', '-3C', '-3E', '-3C', '-3E', '-26', '-27-27', '-7C', '-26', '-25', '-3F' ),
					$value
				);
				
				if ( $result != '' ) $result .= '/';
				
				$result .= $value;
			}
		} else { // Note: this requires to have HTTP compatible parameter names (ASCII)
			$q = array(); // collect unlabelled query parameters here
			
			foreach ( $params as $name => $value ) {
				if ( is_string( $name ) && ( $name != '' ) ) {
					$value = $name . '=' . rawurlencode( $value );
					
					if ( $result != '' ) $result .= '&';
					
					$result .= $value;
				} else {
					$q[] = $value;
				}
			}
			if ( count( $q ) > 0 ) { // prepend encoding for unlabelled parameters
				if ( $result != '' ) $result = '&' . $result;
				$result = 'x=' . rawurlencode( SMWInfolink::encodeParameters( $q, true ) ) . $result;
			}
		}
		
		return $result;
	}

	/**
	 * Obtain an array of parameters from the parameters given to some HTTP service.
	 * In particular, this function perfoms all necessary decoding as may be needed, e.g.,
	 * to recover the proper paramter strings after encoding for use in wiki title names
	 * as done by SMWInfolink::encodeParameters().
	 *
	 * If $allparams is set to true, it is assumed that further data should be obtained
	 * from the global $wgRequest, and all given parameters are read.
	 *
	 * $titleparam is the string extracted by MediaWiki from special page calls of the
	 * form Special:Something/titleparam
	 * Note: it is assumed that the given $titleparam is already urldecoded, as is normal
	 * when getting such parameters from MediaWiki. SMW-escaped parameters largely prevent
	 * double decoding effects (i.e. there are no new "%" after one pass of urldecoding)
	 *
	 * The function SMWInfolink::encodeParameters() can be used to create a suitable
	 * encoding. It is strongly recommended to not create any code that depends on the
	 * concrete way of how parameters are encoded within this function, and to always use
	 * the respective encoding/decoding methods instead.
	 * 
	 * @param string $titleParam
	 * @param boolean $allParams
	 */
	static public function decodeParameters( $titleParam = '', $allParams = false ) {
		global $wgRequest;
		
		$result = array();
		
		if ( $allParams ) {
			$result = $wgRequest->getValues();
			
			if ( array_key_exists( 'x', $result ) ) { // Considered to be part of the title param.
				if ( $titleParam != '' ) $titleParam .= '/';
				$titleParam .= $result['x'];
				unset( $result['x'] );
			}
		}
		
		if ( is_array( $titleParam ) ) {
			return $titleParam;
		} elseif ( $titleParam != '' ) {
			// unescape $p; escaping scheme: all parameters rawurlencoded, "-" and "/" urlencoded, all "%" replaced by "-", parameters then joined with /
			$ps = explode( '/', $titleParam ); // params separated by / here (compatible with wiki link syntax)
			
			foreach ( $ps as $p ) {
				if ( $p != '' ) {
					$p = rawurldecode( str_replace( '-', '%', $p ) );
					$parts = explode( '=', $p, 2 );
					
					if ( count( $parts ) > 1 ) {
						$result[$parts[0]] = $parts[1];
					} else {
						$result[] = $p;
					}
				}
			}
		}
		
		return $result;
	}

}