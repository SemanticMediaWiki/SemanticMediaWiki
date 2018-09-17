<?php

use SMW\Site;

/**
 * This class mainly is a container to store URLs for the factbox in a
 * clean way. The class provides methods for creating source code for
 * realising them in wiki or html contexts.
 *
 * @license GNU GPL v2+
 * @since 1.0
 *
 * @author Markus Krötzsch
 * @author Jeroen De Dauw
 * @author mwjames
 */
class SMWInfolink {

	const LINK_UPPER_LENGTH_RESTRICTION = 2000;

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
	 * @var array
	 */
	private $linkAttributes = [];

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
	 * @var boolean
	 */
	private $isRestricted = false;

	/**
	 * @var boolean
	 */
	private $isCompactLink = false;

	/**
	 * Create a new link to some internal page or to some external URL.
	 *
	 * @param boolean $internal Indicates whether $target is a page name (true) or URL (false).
	 * @param string $caption The label for the link.
	 * @param string $target The actual link target.
	 * @param mixed $style CSS class of a span to embedd the link into, or false if no extra style is required.
	 * @param array $params Array of parameters, format $name => $value, if any.
	 */
	public function __construct( $internal, $caption, $target, $style = false, array $params = [] ) {
		$this->mInternal = $internal;
		$this->mCaption = $caption;
		$this->mTarget = $target;
		$this->mStyle = $style;
		$this->mParams = $params;
		$this->setCompactLink( $GLOBALS['smwgCompactLinkSupport'] );
	}

	/**
	 * @since 3.0
	 *
	 * @param boolean $isRestricted
	 */
	public function isRestricted( $isRestricted ) {
		$this->isRestricted = (bool)$isRestricted;
	}

	/**
	 * @since 3.0
	 *
	 * @param boolean $isCompactLink
	 */
	public function setCompactLink( $isCompactLink = true ) {
		$this->isCompactLink = (bool)$isCompactLink;
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
	public static function newInternalLink( $caption, $target, $style = false, array $params = [] ) {
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
	public static function newExternalLink( $caption, $url, $style = false, array $params = [] ) {
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

		$infolink = new SMWInfolink(
			true,
			$caption,
			$wgContLang->getNsText( NS_SPECIAL ) . ':SearchByProperty',
			$style,
			[ ':' . $propertyName, $propertyValue ] // `:` is marking that the link was auto-generated
		);

		// Link that reaches a length restriction will most likely cause a
		// "HTTP 414 "Request URI too long ..." therefore prevent a link creation
		if ( mb_strlen( $propertyName . $propertyValue ) > self::LINK_UPPER_LENGTH_RESTRICTION ) {
			$infolink->isRestricted( true );
		}

		return $infolink;
	}

	/**
	 * Static function to construct links to inverse property searches.
	 *
	 * @param string $caption The label for the link.
	 * @param string $subject
	 * @param string $propertyName
	 * @param mixed $style CSS class of a span to embed the link into, or false if no extra style is required.
	 *
	 * @return SMWInfolink
	 */
	public static function newInversePropertySearchLink( $caption, $subject, $propertyname, $style = false ) {
		global $wgContLang;
		return new SMWInfolink(
			true,
			$caption,
			$wgContLang->getNsText( NS_SPECIAL ) . ':PageProperty',
			$style,
			[ $subject . '::' . $propertyname ]
		);
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
		return new SMWInfolink(
			true,
			$caption,
			$wgContLang->getNsText( NS_SPECIAL ) . ':Browse',
			$style,
			[ ':' . $titleText ]
		);
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
	 * Modify link attributes
	 *
	 * @since 3.0
	 *
	 * @param array $linkAttributes
	 */
	public function setLinkAttributes( array $linkAttributes ) {
		$this->linkAttributes = $linkAttributes;
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

		if ( $this->isRestricted ) {
			return '';
		}

		if ( $this->mStyle !== false ) {
			SMWOutputs::requireResource( 'ext.smw.style' );
			$start = "<span class=\"$this->mStyle\">";
			$end = '</span>';
		} else {
			$start = '';
			$end = '';
		}

		if ( $this->mInternal ) {
			if ( count( $this->mParams ) > 0 ) {

				$query = self::encodeParameters( $this->mParams );

				if ( $this->isCompactLink ) {
					$query = self::encodeCompactLink( $query );
				}

				$titletext = $this->mTarget . '/' . $query;
			} else {
				$titletext = $this->mTarget;
			}

			$title = Title::newFromText( $titletext );

			if ( $title !== null ) {
				if ( $outputformat == SMW_OUTPUT_WIKI ) {
					$link = "[[$titletext|$this->mCaption]]";
				} elseif ( $outputformat == SMW_OUTPUT_RAW ) {
					return $this->getURL();
				} else { // SMW_OUTPUT_HTML, SMW_OUTPUT_FILE
					$link = $this->getLinker( $linker )->link( $title, $this->mCaption, $this->linkAttributes );
				}
			} else {
				// Title creation failed, maybe illegal symbols or too long; make
				// a direct URL link (only possible if offending target parts belong
				// to some parameter that can be separated from title text, e.g.
				// as in Special:Bla/il<leg>al -> Special:Bla&p=il&lt;leg&gt;al)
				$title = Title::newFromText( $this->mTarget );

				// Just give up due to the title being bad, normally this would
				// indicate a software bug
				if ( $title === null ) {
					return '';
				}

				$query = self::encodeParameters( $this->mParams, $this->isCompactLink );

				if ( $outputformat == SMW_OUTPUT_WIKI ) {

					if ( $this->isCompactLink ) {
						$query = self::encodeCompactLink( $query, false );
					}

					$link = '[' . $title->getFullURL(  $query ) . " $this->mCaption]";
				} else { // SMW_OUTPUT_HTML, SMW_OUTPUT_FILE

					if ( $this->isCompactLink ) {
						$query = self::encodeCompactLink( $query, true );
					} else {
						// #511, requires an array
						$query = wfCgiToArray( $query );
					}

					$link = $this->getLinker( $linker )->link(
						$title,
						$this->mCaption,
						$this->linkAttributes,
						$query
					);
				}
			}
		} else {
			$target = $this->getURL();

			if ( $outputformat == SMW_OUTPUT_WIKI ) {
				$link = "[$target $this->mCaption]";
			} else { // SMW_OUTPUT_HTML, SMW_OUTPUT_FILE
				$link = '<a href="' . htmlspecialchars( $target ) . "\">$this->mCaption</a>";
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

		$query = self::encodeParameters( $this->mParams, $this->isCompactLink );

		if ( $this->isCompactLink && $query !== '' ) {
			$query = self::encodeCompactLink( $query, true );
		}

		if ( !$this->mInternal ) {
			return $this->buildTarget( $query );
		}

		$title = Title::newFromText( $this->mTarget );

		if ( $title !== null ) {
			return $title->getFullURL( $query );
		}

		// the title was bad, normally this would indicate a software bug
		return '';
	}

	/**
	 * @since 3.0
	 *
	 * @return string
	 */
	public function getLocalURL() {

		$query = self::encodeParameters( $this->mParams, $this->isCompactLink );

		if ( $this->isCompactLink && $query !== '' ) {
			$query = self::encodeCompactLink( $query, true );
		}

		if ( !$this->mInternal ) {
			return $this->buildTarget( $query );
		}

		$title = Title::newFromText( $this->mTarget );

		if ( $title !== null ) {
			return $title->getLocalURL( $query );
		}

		 // the title was bad, normally this would indicate a software bug
		return '';
	}

	/**
	 * Return a Linker object, using the parameter $linker if not null, and creatng a new one
	 * otherwise. $linker is usually a user skin object, while the fallback linker object is
	 * not customised to user settings.
	 *
	 * @return Linker
	 */
	protected function getLinker( &$linker = null ) {
		if ( is_null( $linker ) ) {
			$linker = new Linker;
		}
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
				if ( is_string( $name ) && ( $name !== '' ) ) {
					$value = $name . '=' . $value;
				}
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
					[ '-', '#', "\n", ' ', '/', '[', ']', '<', '>', '&lt;', '&gt;', '&amp;', '\'\'', '|', '&', '%', '?', '$', "\\", ";", "_" ],
					[ '-2D', '-23', '-0A', '-20', '-2F', '-5B', '-5D', '-3C', '-3E', '-3C', '-3E', '-26', '-27-27', '-7C', '-26', '-25', '-3F', '-24', '-5C', "-3B", "-5F" ],
					$value
				);

				if ( $result !== '' ) {
					$result .= '/';
				}

				$result .= $value;
			}
		} else { // Note: this requires to have HTTP compatible parameter names (ASCII)
			$q = []; // collect unlabelled query parameters here

			foreach ( $params as $name => $value ) {
				if ( is_string( $name ) && ( $name !== '' ) ) {
					$value = rawurlencode( $name ) . '=' . rawurlencode( $value );

					if ( $result !== '' ) {
						$result .= '&';
					}

					$result .= $value;
				} else {
					$q[] = $value;
				}
			}
			if ( count( $q ) > 0 ) { // prepend encoding for unlabelled parameters
				if ( $result !== '' ) {
					$result = '&' . $result;
				}
				$result = 'x=' . rawurlencode( self::encodeParameters( $q, true ) ) . $result;
			}
		}

		return $result;
	}

	/**
	 * Obtain an array of parameters from the parameters given to some HTTP service.
	 * In particular, this function performs all necessary decoding as may be needed, e.g.,
	 * to recover the proper parameter strings after encoding for use in wiki title names
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

		$result = [];

		if ( $allParams ) {
			$result = $wgRequest->getValues();

			if ( array_key_exists( 'x', $result ) ) { // Considered to be part of the title param.
				if ( $titleParam !== '' ) {
					$titleParam .= '/';
				}
				$titleParam .= $result['x'];
				unset( $result['x'] );
			}
		}

		if ( is_array( $titleParam ) ) {
			return $titleParam;
		} elseif ( $titleParam !== '' ) {
			// unescape $p; escaping scheme: all parameters rawurlencoded, "-" and "/" urlencoded, all "%" replaced by "-", parameters then joined with /
			$ps = explode( '/', $titleParam ); // params separated by / here (compatible with wiki link syntax)

			foreach ( $ps as $p ) {
				if ( $p !== '' ) {
					$result[] = rawurldecode( str_replace( '-', '%', $p ) );
				}
			}
		}

		return $result;
	}

	/**
	 * @since 3.0
	 *
	 * @param string $value
	 *
	 * @return string|array
	 */
	public static function encodeCompactLink( $value, $compound = false ) {

		// Expect to gain on larger strings and set an identifier to
		// distinguish between compressed and non compressed
		if ( mb_strlen( $value ) > 150 ) {
			$value =  'c:' . gzdeflate( $value, 9 );
		}

		// https://en.wikipedia.org/wiki/Base64#URL_applications
		// The MW parser swallows `__` and transforms it into a simple `_`
		// hence we need to encode it once more
		$value = rtrim( str_replace( '__', '.', strtr( base64_encode( $value ), '+/', '-_' ) ), '=' );

		if ( $compound ) {
			return [ 'cl' => $value ];
		}

		return "cl:$value";
	}

	/**
	 * @since 3.0
	 *
	 * @param string $value
	 *
	 * @return string
	 */
	public static function decodeCompactLink( $value ) {

		if ( !is_string( $value ) || mb_substr( $value, 0, 3 ) !== 'cl:' ) {
			return $value;
		}

		$value = mb_substr( $value, 3 );

		$value = base64_decode(
			str_pad( strtr( str_replace( '.', '__', $value ), '-_', '+/' ), strlen( $value ) % 4, '=', STR_PAD_RIGHT )
		);

		// Compressed?
		if ( mb_substr( $value, 0, 2 ) === 'c:' ) {
			$val = @gzinflate( mb_substr( $value, 2 ) );

			// Guessing that MediaWiki swallowed the last `_`
			if ( $val === false ) {
				$val = @gzinflate( mb_substr( $value , 2 ) . '?' );
			}

			$value = $val;
		}

		// Normalize if nceessary for those that are "encoded for use in a
		// MediaWiki page title"
		if ( mb_substr( $value, 0, 2 ) === 'x=' ) {
			$value = str_replace( [ 'x=', '=-&' , '&', '%2F' ], [ '' , '=-2D&', '/', '/' ], $value );
		}

		return $value;
	}

	private function buildTarget( $query ) {

		$target = $this->mTarget;

		if ( count( $this->mParams ) > 0 ) {
			if ( strpos( Site::wikiurl(), '?' ) === false ) {
				$target = $this->mTarget . '?' . $query;
			} else {
				$target = $this->mTarget . '&' . $query;
			}
		}

		return $target;
	}

}
