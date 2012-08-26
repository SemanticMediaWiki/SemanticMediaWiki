<?php
/**
 * @file
 * @ingroup SMWDataValues
 */

/**
 * This datavalue implements special processing suitable for defining
 * wikipages as values of properties.
 *
 * The class can support general wiki pages, or pages of a fixed
 * namespace, Whether a namespace is fixed is decided based on the
 * type ID when the object is constructed.
 *
 * The short display simulates the behaviour of the MediaWiki "pipe trick"
 * but always includes fragments. This can be overwritten by setting a
 * caption, which is also done by default when generating a value from user
 * input. The long display always includes all relevant information. Only if a
 * fixed namespace is used for the datatype, the namespace prefix is omitted.
 * This behavior has changed in SMW 1.7: up to this time, short displays have
 * always inlcuded the namespace and long displays used the pipe trick, leading
 * to a paradoxical confusion of "long" and "short".
 *
 * @author Nikolas Iwan
 * @author Markus Krötzsch
 * @ingroup SMWDataValues
 */
class SMWWikiPageValue extends SMWDataValue {

	/**
	 * Fragment text for user-specified title. Not stored, but kept for
	 * printout on page.
	 * @var string
	 */
	protected $m_fragment = '';

	/**
	 * Full titletext with prefixes, including interwiki prefix.
	 * Set to empty string if not computed yet.
	 * @var string
	 */
	protected $m_prefixedtext = '';

	/**
	 * Cache for the related MW page ID.
	 * Set to -1 if not computed yet.
	 * @var integer
	 */
	protected $m_id = -1;

	/**
	 * Cache for the related MW title object.
	 * Set to null if not computed yet.
	 * @var Title
	 */
	protected $m_title = null;

	/**
	 * If this has a value other than NS_MAIN, the datavalue will only
	 * accept pages in this namespace. This field is initialized when
	 * creating the object (based on the type id or base on the preference
	 * of some subclass); it is not usually changed afterwards.
	 * @var integer
	 */
	protected $m_fixNamespace = NS_MAIN;

	public function __construct( $typeid ) {
		parent::__construct( $typeid );
		switch ( $typeid ) {
			case '__typ':
				$this->m_fixNamespace = SMW_NS_TYPE;
			break;
			case '_wpp' : case '__sup':
				$this->m_fixNamespace = SMW_NS_PROPERTY;
			break;
			case '_wpc' : case '__suc': case '__sin':
				$this->m_fixNamespace = NS_CATEGORY;
			break;
			case '_wpf' : case '__spf':
				$this->m_fixNamespace = SF_NS_FORM;
			break;
			default: // case '_wpg':
				$this->m_fixNamespace = NS_MAIN;
		}
	}

	protected function parseUserValue( $value ) {
		global $wgContLang;

		// support inputs like " [[Test]] ";
		// note that this only works in pages if $smwgLinksInValues is set to true
		$value = ltrim( rtrim( $value, ' ]' ), ' [' );

		if ( $this->m_caption === false ) {
			$this->m_caption = $value;
		}

		if ( $value !== '' ) {
			if ( $value[0] == '#' ) {
				if ( is_null( $this->m_contextPage ) ) {
					$this->addError( wfMessage( 'smw_notitle', $value )->inContentLanguage()->text() );
					return;
				} else {
					$this->m_title = Title::makeTitle( $this->m_contextPage->getNamespace(),
						$this->m_contextPage->getDBkey(), substr( $value, 1 ),
						$this->m_contextPage->getInterwiki() );
				}
			} else {
				$this->m_title = Title::newFromText( $value, $this->m_fixNamespace );
			}

			/// TODO: Escape the text so users can see punctuation problems (bug 11666).
			if ( is_null( $this->m_title ) ) {
				$this->addError( wfMessage( 'smw_notitle', $value )->inContentLanguage()->text() );
			} elseif ( ( $this->m_fixNamespace != NS_MAIN ) &&
				 ( $this->m_fixNamespace != $this->m_title->getNamespace() ) ) {
				$this->addError( wfMessage( 'smw_wrong_namespace',
					$wgContLang->getNsText( $this->m_fixNamespace ) )->inContentLanguage()->text() );
			} else {
				$this->m_fragment = str_replace( ' ', '_', $this->m_title->getFragment() );
				$this->m_prefixedtext = '';
				$this->m_id = -1; // unset id
				$this->m_dataitem = SMWDIWikiPage::newFromTitle( $this->m_title, $this->m_typeid );
			}
		} else {
			$this->addError(  wfMessage( 'smw_notitle', $value )->inContentLanguage()->text() );
		}
	}

	/**
	 * @see SMWDataValue::loadDataItem()
	 * @param $dataitem SMWDataItem
	 * @return boolean
	 */
	protected function loadDataItem( SMWDataItem $dataItem ) {
		if ( $dataItem->getDIType() == SMWDataItem::TYPE_CONTAINER ) {
			// might throw an exception, we just pass it through
			$dataItem = $dataItem->getSemanticData()->getSubject();
		}

		if ( $dataItem->getDIType() == SMWDataItem::TYPE_WIKIPAGE ) {
			$this->m_dataitem = $dataItem;
			$this->m_id = -1;
			$this->m_title = null;
			$this->m_fragment = $dataItem->getSubobjectName();
			$this->m_prefixedtext = '';
			$this->m_caption = false; // this class can handle this

			if ( ( $this->m_fixNamespace != NS_MAIN ) &&
				( $this->m_fixNamespace != $dataItem->getNamespace() ) ) {
					global $wgContLang;
					$this->addError( wfMessage( 'smw_wrong_namespace',
						$wgContLang->getNsText( $this->m_fixNamespace ) )->inContentLanguage()->text() );
			}
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Display the value on a wiki page. This is used to display the value
	 * in the place where it was annotated on a wiki page. The desired
	 * behavior is that the display in this case looks as if no property
	 * annotation had been given, i.e. an annotation [[property::page|foo]]
	 * should display like [[page|foo]] in MediaWiki. But this should lead
	 * to a link, not to a category assignment. This means that:
	 *
	 * (1) If Image: is used (instead of Media:) then let MediaWiki embed
	 * the image.
	 *
	 * (2) If Category: is used, treat it as a page and link to it (do not
	 * categorize the page)
	 *
	 * (3) Preserve everything given after "|" for display (caption, image
	 * parameters, ...)
	 *
	 * (4) Use the (default) caption for display. When the value comes from
	 * user input, this includes the full value that one would also see in
	 * MediaWiki.
	 *
	 * @param $linked mixed generate links if not null or false
	 * @return string
	 */
	public function getShortWikiText( $linked = null ) {
		if ( is_null( $linked ) || $linked === false ||
			$this->m_outformat == '-' || !$this->isValid() ||
			$this->m_caption === '' ) {
			return $this->m_caption !== false ? $this->m_caption : $this->getWikiValue();
		} else {
			if ( $this->m_dataitem->getNamespace() == NS_FILE ) {
				$linkEscape = '';
				$defaultCaption = '|' . $this->getShortCaptionText() . '|frameless|border|text-top';
			} else {
				$linkEscape = ':';
				$defaultCaption = '|' . $this->getShortCaptionText();
			}
			if ( $this->m_caption === false ) {
				return '[[' . $linkEscape . $this->getWikiLinkTarget() . $defaultCaption . ']]';
			} else {
				return '[[' . $linkEscape . $this->getWikiLinkTarget() . '|' . $this->m_caption . ']]';
			}
		}
	}

	/**
	 * Display the value as in getShortWikiText() but create HTML.
	 * The only difference is that images are not embedded.
	 *
	 * @param $linker mixed the Linker object to use or null if no linking is desired
	 * @return string
	 */
	public function getShortHTMLText( $linker = null ) {
		// init the Title object, may reveal hitherto unnoticed errors:
		if ( !is_null( $linker ) && $linker !== false &&
				$this->m_caption !== '' && $this->m_outformat != '-' ) {
			$this->getTitle();
		}

		if ( is_null( $linker ) || $linker === false || !$this->isValid() ||
				$this->m_outformat == '-' || $this->m_caption === '' ) {
			return htmlspecialchars( $this->m_caption !== false ? $this->m_caption : $this->getWikiValue() );
		} else {
			$caption = htmlspecialchars(
				$this->m_caption !== false ? $this->m_caption : $this->getShortCaptionText() );
			if ( $this->getNamespace() == NS_MEDIA ) { // this extra case *is* needed
				return $linker->makeMediaLinkObj( $this->getTitle(), $caption );
			} else {
				return $linker->makeLinkObj( $this->getTitle(), $caption );
			}
		}
	}

	/**
	 * Display the "long" value on a wiki page. This behaves largely like
	 * getShortWikiText() but does not use the caption. Instead, it always
	 * takes the long display form (wiki value).
	 *
	 * @param $linked mixed if true the result will be linked
	 * @return string
	 */
	public function getLongWikiText( $linked = null ) {
		if ( !$this->isValid() ) {
			return $this->getErrorText();
		}

		if ( is_null( $linked ) || $linked === false || $this->m_outformat == '-' ) {
			return $this->getWikiValue();
		} elseif ( $this->m_dataitem->getNamespace() == NS_FILE ) {
			// Embed images and other files
			// Note that the embedded file links to the image, hence needs no additional link text.
			// There should not be a linebreak after an impage, just like there is no linebreak after
			// other values (whether formatted or not).
			return '[[' . $this->getWikiLinkTarget() . '|' .
				$this->getLongCaptionText() . '|frameless|border|text-top]]';
		} else {
			return '[[:' . $this->getWikiLinkTarget() . '|' . $this->getLongCaptionText() . ']]';
		}
	}

	/**
	 * Display the "long" value in HTML. This behaves largely like
	 * getLongWikiText() but does not embed images.
	 *
	 * @param $linker mixed if a Linker is given, the result will be linked
	 * @return string
	 */
	public function getLongHTMLText( $linker = null ) {
		// init the Title object, may reveal hitherto unnoticed errors:
		if ( !is_null( $linker ) && ( $this->m_outformat != '-' ) ) {
			$this->getTitle();
		}
		if ( !$this->isValid() ) {
			return $this->getErrorText();
		}

		if ( is_null( $linker ) || $this->m_outformat == '-' ) {
			return htmlspecialchars( $this->getWikiValue() );
		} elseif ( $this->getNamespace() == NS_MEDIA ) { // this extra case is really needed
			return $linker->makeMediaLinkObj( $this->getTitle(),
				htmlspecialchars( $this->getLongCaptionText() ) );
		} else { // all others use default linking, no embedding of images here
			return $linker->makeLinkObj( $this->getTitle(),
				htmlspecialchars( $this->getLongCaptionText() ) );
		}
	}

	/**
	 * Return a string that could be used in an in-page property assignment
	 * for setting this value. This does not include initial ":" for
	 * escaping things like Category: links since the property value does
	 * not include such escapes either. Fragment information is included.
	 * Namespaces are omitted if a fixed namespace is used, since they are
	 * not needed in this case when making a property assignment.
	 *
	 * @return string
	 */
	public function getWikiValue() {
		return ( $this->m_fixNamespace == NS_MAIN ? $this->getPrefixedText() : $this->getText() ) .
			( $this->m_fragment !== '' ? "#{$this->m_fragment}" : '' );
	}

	public function getHash() {
		return $this->isValid() ? $this->getPrefixedText() : implode( "\t", $this->getErrors() );
	}

	/**
	 * Create links to mapping services based on a wiki-editable message.
	 * The parameters available to the message are:
	 * $1: urlencoded article name (no namespace)
	 *
	 * @return array
	 */
	protected function getServiceLinkParams() {
		if ( $this->isValid() ) {
			return array( rawurlencode( str_replace( '_', ' ', $this->m_dataitem->getDBkey() ) ) );
		} else {
			return array();
		}
	}

///// special interface for wiki page values

	/**
	 * Return according Title object or null if no valid value was set.
	 * null can be returned even if this object returns true for isValid(),
	 * since the latter function does not check whether MediaWiki can really
	 * make a Title out of the given data.
	 * However, isValid() will return false *after* this function failed in
	 * trying to create a title.
	 *
	 * @return Title
	 */
	public function getTitle() {
		if ( ( $this->isValid() ) && is_null( $this->m_title ) ) {
			$this->m_title = $this->m_dataitem->getTitle();

			if ( is_null( $this->m_title ) ) { // should not normally happen, but anyway ...
				global $wgContLang;
				$this->addError( wfMessage(
					'smw_notitle',
					$wgContLang->getNsText( $this->m_dataitem->getNamespace() ) . ':' . $this->m_dataitem->getDBkey()
				)->inContentLanguage()->text() );
			}
		}

		return $this->m_title;
	}

	/**
	 * Get MediaWiki's ID for this value or 0 if not available.
	 *
	 * @return integer
	 */
	public function getArticleID() {
		if ( $this->m_id === false ) {
			$this->m_id = !is_null( $this->getTitle() ) ? $this->m_title->getArticleID() : 0;
		}

		return $this->m_id;
	}

	/**
	 * Get namespace constant for this value.
	 *
	 * @return integer
	 */
	public function getNamespace() {
		return $this->m_dataitem->getNamespace();
	}

	/**
	 * Get DBKey for this value. Subclasses that allow for values that do not
	 * correspond to wiki pages may choose a DB key that is not a legal title
	 * DB key but rather another suitable internal ID. Thus it is not suitable
	 * to use this method in places where only MediaWiki Title keys are allowed.
	 *
	 * @return string
	 */
	public function getDBkey() {
		return $this->m_dataitem->getDBkey();
	}

	/**
	 * Get text label for this value, just like Title::getText().
	 *
	 * @return string
	 */
	public function getText() {
		return str_replace( '_', ' ', $this->m_dataitem->getDBkey() );
	}

	/**
	 * Get the prefixed text for this value, including a localized namespace
	 * prefix.
	 *
	 * @return string
	 */
	public function getPrefixedText() {
		global $wgContLang;
		if ( $this->m_prefixedtext === '' ) {
			if ( $this->isValid() ) {
				$nstext = $wgContLang->getNSText( $this->m_dataitem->getNamespace() );
				$this->m_prefixedtext =
					( $this->m_dataitem->getInterwiki() !== '' ?
						$this->m_dataitem->getInterwiki() . ':' : '' ) .
					( $nstext !== '' ? "$nstext:" : '' ) . $this->getText();
			} else {
				$this->m_prefixedtext = 'NO_VALID_VALUE';
			}
		}
		return $this->m_prefixedtext;
	}

	/**
	 * Get interwiki prefix or empty string.
	 *
	 * @return string
	 */
	public function getInterwiki() {
		return $this->m_dataitem->getInterwiki();
	}

	/**
	 * Get a short caption used to label this value. In particular, this
	 * omits namespace and interwiki prefixes (similar to the MediaWiki
	 * "pipe trick"). Fragments are included unless they start with an
	 * underscore (used for generated fragment names that are not helpful
	 * for users and that might change easily).
	 *
	 * @since 1.7
	 * @return string
	 */
	protected function getShortCaptionText() {
		if ( $this->m_fragment !== '' && $this->m_fragment[0] != '_' ) {
			$fragmentText = '#' . $this->m_fragment;
		} else {
			$fragmentText = '';
		}
		return $this->getText() . $fragmentText;
	}

	/**
	 * Get a long caption used to label this value. In particular, this
	 * includes namespace and interwiki prefixes, while fragments are only
	 * included if they do not start with an underscore (used for generated
	 * fragment names that are not helpful for users and that might change
	 * easily).
	 *
	 * @since 1.7
	 * @return string
	 */
	protected function getLongCaptionText() {
		if ( $this->m_fragment !== '' && $this->m_fragment[0] != '_' ) {
			$fragmentText = '#' . $this->m_fragment;
		} else {
			$fragmentText = '';
		}
		return ( $this->m_fixNamespace == NS_MAIN ? $this->getPrefixedText() : $this->getText() ) . $fragmentText;
	}

	/**
	 * Compute a text that can be used in wiki text to link to this
	 * datavalue. Processing includes some escaping and adding the
	 * fragment.
	 *
	 * @since 1.7
	 * @return string
	 */
	protected function getWikiLinkTarget() {
		return str_replace( "'", '&#x0027;', $this->getPrefixedText() ) .
			( $this->m_fragment !== '' ? "#{$this->m_fragment}" : '' );
	}

	/**
	 * Find the sortkey for this object.
	 *
	 * @deprecated Use SMWStore::getWikiPageSortKey(). Will vanish before SMW 1.7
	 *
	 * @return string sortkey
	 */
	public function getSortKey() {
		return smwfGetStore()->getWikiPageSortKey( $this->m_dataitem );
	}

	/**
	 * Static function for creating a new wikipage object from
	 * data as it is typically stored internally. In particular,
	 * the title string is supposed to be in DB key form.
	 *
	 * @note The resulting wikipage object might be invalid if
	 * the provided title is not allowed. An object is returned
	 * in any case.
	 *
	 * @deprecated This method will vanish before SMW 1.7. If you really need this, simply copy its code.
	 *
	 * @return SMWWikiPageValue
	 */
	static public function makePage( $dbkey, $namespace, $ignoredParameter = '', $interwiki = '' ) {
		$diWikiPage = new SMWDIWikiPage( $dbkey, $namespace, $interwiki );
		$dvWikiPage = new SMWWikiPageValue( '_wpg' );
		$dvWikiPage->setDataItem( $diWikiPage );
		return $dvWikiPage;
	}

	/**
	 * Static function for creating a new wikipage object from a
	 * MediaWiki Title object.
	 *
	 * @deprecated This method will vanish before SMW 1.7. If you really need this, simply copy its code.
	 *
	 * @return SMWWikiPageValue
	 */
	static public function makePageFromTitle( Title $title ) {
		$dvWikiPage = new SMWWikiPageValue( '_wpg' );
		$diWikiPage = SMWDIWikiPage::newFromTitle( $title );
		$dvWikiPage->setDataItem( $diWikiPage );
		$dvWikiPage->m_title = $title; // optional, just for efficiency
		return $dvWikiPage;
	}

}

