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
 * @author Nikolas Iwan
 * @author Markus Krötzsch
 * @ingroup SMWDataValues
 */
class SMWWikiPageValue extends SMWDataValue {

	/**
	 * The isolated title as text. Always set when this object is valid.
	 * @var string
	 */
	protected $m_textform;

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
		$value = ltrim( rtrim( $value, ' ]' ), ' [' ); // support inputs like " [[Test]] "
		if ( $this->m_caption === false ) {
			$this->m_caption = $value;
		}

		if ( $value != '' ) {
			$this->m_title = Title::newFromText( $value, $this->m_fixNamespace );
			///TODO: Escape the text so users can see punctuation problems (bug 11666).
			if ( $this->m_title === null ) {
				smwfLoadExtensionMessages( 'SemanticMediaWiki' );
				$this->addError( wfMsgForContent( 'smw_notitle', $value ) );
			} elseif ( ( $this->m_fixNamespace != NS_MAIN ) &&
				 ( $this->m_fixNamespace != $this->m_title->getNamespace() ) ) {
				smwfLoadExtensionMessages( 'SemanticMediaWiki' );
				$this->addError( wfMsgForContent( 'smw_wrong_namespace', $wgContLang->getNsText( $this->m_fixNamespace ) ) );
			} else {
				$this->m_textform = $this->m_title->getText();
				$this->m_fragment = $this->m_title->getFragment();
				$this->m_prefixedtext = '';
				$this->m_id = -1; // unset id
				$this->m_dataitem = SMWDIWikiPage::newFromTitle( $this->m_title, $this->m_typeid );
			}
		} else {
			smwfLoadExtensionMessages( 'SemanticMediaWiki' );
			$this->addError(  wfMsgForContent( 'smw_notitle', $value ) );
		}
	}

	/**
	 * @see SMWDataValue::loadDataItem()
	 * @param $dataitem SMWDataItem
	 * @return boolean
	 */
	protected function loadDataItem( SMWDataItem $dataItem ) {
		if ( $dataItem->getDIType() == SMWDataItem::TYPE_WIKIPAGE ) {
			$this->m_dataitem = $dataItem;
			$this->m_textform = str_replace( '_', ' ', $dataItem->getDBkey() );
			$this->m_id = -1;
			$this->m_title = null;
			$this->m_fragment = $this->m_prefixedtext = '';
			$this->m_caption = false;
			if ( ( $this->m_fixNamespace != NS_MAIN ) && ( $this->m_fixNamespace != $dataItem->getNamespace() ) ) {
				smwfLoadExtensionMessages( 'SemanticMediaWiki' );
				$this->addError( wfMsgForContent( 'smw_notitle', $this->getPrefixedText() ) );
			}
			return true;
		} else {
			return false;
		}
	}

	public function getShortWikiText( $linked = null ) {
		if ( ( $linked === null ) || ( $linked === false ) || ( $this->m_outformat == '-' ) || ( !$this->isValid() ) || ( $this->m_caption === '' ) ) {
			return $this->getCaption();
		} else {
			return '[[:' . str_replace( "'", '&#x0027;', $this->getPrefixedText() ) .
			        ( $this->m_fragment ? "#{$this->m_fragment}" : '' ) . '|' . $this->getCaption() . ']]';
		}
	}

	public function getShortHTMLText( $linker = null ) {
		if ( ( $linker !== null ) && ( $this->m_caption !== '' ) && ( $this->m_outformat != '-' ) ) $this->getTitle(); // init the Title object, may reveal hitherto unnoticed errors
		if ( is_null( $linker ) || $linker === false || ( !$this->isValid() ) || ( $this->m_outformat == '-' ) || ( $this->m_caption === '' ) ) {
			return htmlspecialchars( $this->getCaption() );
		} elseif ( $this->getNamespace() == NS_MEDIA ) { // this extra case *is* needed
			return $linker->makeMediaLinkObj( $this->getTitle(), $this->getCaption() );
		} else {
			return $linker->makeLinkObj( $this->getTitle(), htmlspecialchars( $this->getCaption() ) );
		}
	}

	/**
	 * @note The getLong... functions of this class always hide the fragment. Fragments are currently
	 * not stored, and hence should not be shown in the Factbox (where the getLongWikiText method is used).
	 * In all other uses, values come from the store and do not have fragments anyway.
	 */
	public function getLongWikiText( $linked = null ) {
		if ( !$this->isValid() ) {
			return $this->getErrorText();
		}
		if ( ( $linked === null ) || ( $linked === false ) || ( $this->m_outformat == '-' ) ) {
			return $this->m_fixNamespace == NS_MAIN ? $this->getPrefixedText():$this->getText();
		} elseif ( $this->m_dataitem->getNamespace() == NS_FILE ) {
			// For images and other files, embed them and display
			// their name, instead of just displaying their name
			$fileName = str_replace( "'", '&#x0027;', $this->getPrefixedText() );
			 return '[[' . $fileName . '|' . $this->m_textform . '|frameless|border|text-top]]' . "<br />\n" . '[[:' . $fileName . '|' . $this->m_textform . ']]';
		} else {
			return '[[:' . str_replace( "'", '&#x0027;', $this->getPrefixedText() ) . '|' . $this->m_textform . ']]';
		}
	}

	public function getLongHTMLText( $linker = null ) {
		if ( ( $linker !== null ) && ( $this->m_outformat != '-' ) ) { $this->getTitle(); } // init the Title object, may reveal hitherto unnoticed errors
		if ( !$this->isValid() ) {
			return $this->getErrorText();
		}
		if ( ( $linker === null ) || ( $this->m_outformat == '-' ) ) {
			return htmlspecialchars( $this->m_fixNamespace == NS_MAIN ? $this->getPrefixedText():$this->getText() );
		} elseif ( $this->getNamespace() == NS_MEDIA ) { // this extra case is really needed
			return $linker->makeMediaLinkObj( $this->getTitle(), $this->m_textform );
		} else { // all others use default linking, no embedding of images here
			return $linker->makeLinkObj( $this->getTitle(), htmlspecialchars( $this->m_textform ) );
		}
	}

	public function getWikiValue() {
		if ( $this->m_fixNamespace != NS_MAIN ) { // no explicit namespace needed!
			return $this->getText();
		} elseif ( $this->m_dataitem->getNamespace() == NS_CATEGORY ) {
			// escape to enable use in links; todo: not generally required/suitable :-/
			return ':' . $this->getPrefixedText();
		} else {
			return $this->getPrefixedText();
		}
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
		if ( ( $this->isValid() ) && ( $this->m_title === null ) ) {
			$this->m_title = $this->m_dataitem->getTitle();
			if ( $this->m_title === null ) { // should not normally happen, but anyway ...
				global $wgContLang;
				smwfLoadExtensionMessages( 'SemanticMediaWiki' );
				$this->addError( wfMsgForContent( 'smw_notitle', $wgContLang->getNsText( $this->m_dataitem->getNamespace() ) . ':' . $this->m_dataitem->getDBkey() ) );
			}
		}

		return $this->m_title;
	}

	/**
	 * Get MediaWiki's ID for this value, if any.
	 */
	public function getArticleID() {
		if ( $this->m_id === false ) {
			$this->m_id = ( $this->getTitle() !== null ) ? $this->m_title->getArticleID() : 0;
		}
		return $this->m_id;
	}

	/**
	 * Get namespace constant for this value.
	 */
	public function getNamespace() {
		return $this->m_dataitem->getNamespace();
	}

	/**
	 * Get DBKey for this value. Subclasses that allow for values that do not
	 * correspond to wiki pages may choose a DB key that is not a legal title
	 * DB key but rather another suitable internal ID. Thus it is not suitable
	 * to use this method in places where only MediaWiki Title keys are allowed.
	 */
	public function getDBkey() {
		return $this->m_dataitem->getDBkey();
	}

	/// Get text label for this value.
	public function getText() {
		return str_replace( '_', ' ', $this->m_dataitem->getDBkey() );
	}

	/**
	 * Get the prefixed text for this value, including a localised namespace
	 * prefix.
	 *
	 * @return string
	 */
	public function getPrefixedText() {
		global $wgContLang;
		if ( $this->m_prefixedtext == '' ) {
			if ( $this->isValid() ) {
				$nstext = $wgContLang->getNSText( $this->m_dataitem->getNamespace() );
				$this->m_prefixedtext = ( $this->m_dataitem->getInterwiki() != '' ? $this->m_dataitem->getInterwiki() . ':' : '' ) .
							( $nstext != '' ? "$nstext:" : '' ) . $this->m_textform;
			} else {
				$this->m_prefixedtext = 'NO_VALID_VALUE';
			}
		}
		return $this->m_prefixedtext;
	}

	/**
	 * Get interwiki prefix or empty string.
	 */
	public function getInterwiki() {
		return $this->m_dataitem->getInterwiki();
	}

	/**
	 * Get the (default) caption for this value.
	 * If a fixed namespace is set, we do not return the namespace prefix explicitly.
	 */
	protected function getCaption() {
		return $this->m_caption !== false ? $this->m_caption :
		       ( $this->m_fixNamespace == NS_MAIN ? $this->getPrefixedText() : $this->getText() );
	}

	/**
	 * Find the sortkey for this object.
	 * 
	 * @deprecated Use SMWStore::getWikiPageSortKey().
	 *
	 * @return string sortkey
	 */
	public function getSortKey() {
		return smwfGetStore()->getWikiPageSortKey( $this->m_dataitem );
	}

	/**
	 * Init this data value object based on a given Title object.
	 * @deprecated Use setDataItem(); it's easy to create an SMWDIWikiPage from a Title, will vanish before SMW 1.7
	 */
	public function setTitle( $title ) {
		$diWikiPage = SMWDIWikiPage::newFromTitle( $title );
		$this->setDataItem( $diWikiPage );
		$this->m_title = $title; // optional, just for efficiency
	}

	/**
	 * @deprecated Use setDataItem()
	 */
	public function setValues( $dbkey, $namespace, $id = false, $interwiki = '' ) {
		$dataItem = new SMWDIWikiPage( $dbkey, $namespace, $interwiki ); 
		$this->setDataItem( $dataItem);
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

