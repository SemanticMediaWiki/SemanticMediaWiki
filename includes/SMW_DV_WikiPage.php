<?php
/**
 * @file
 * @ingroup SMWDataValues
 */

/**
 * This datavalue implements special processing suitable for defining
 * wikipages as values of properties. In contrast to most other types
 * of values, wiki pages are determined by multiple components, as
 * retruned by their getDBkeys() method: DBkey, namespace, interwiki
 * prefix and sortkey. The last of those has a somewhat nonstandard
 * behaviour, since it is not attached to every wiki page value, but
 * only to those that represent page subjects, which define the sortkey
 * globally for all places where this page value occurs.
 *
 * @author Nikolas Iwan
 * @author Markus Krötzsch
 * @ingroup SMWDataValues
 */
class SMWWikiPageValue extends SMWDataValue {

	protected $m_textform = ''; // the isolated title as text
	protected $m_dbkeyform = ''; // the isolated title in DB form
	protected $m_interwiki = ''; // interwiki prefix or '', actually stored in SMWSQLStore2
	protected $m_sortkey = ''; // key for alphabetical sorting
	protected $m_fragment = ''; // not stored, but kept for printout on page
	protected $m_prefixedtext = ''; // full titletext with prefixes, including interwiki prefix
	protected $m_namespace = NS_MAIN;
	protected $m_id; // false if unset
	protected $m_title = NULL;

	protected $m_fixNamespace = NS_MAIN; // if namespace other than NS_MAIN, restrict inputs to this namespace

	/**
	 * Static function for creating a new wikipage object from
	 * data as it is typically stored internally. In particular,
	 * the title string is supposed to be in DB key form.
	 * @note The resulting wikipage object might be invalid if
	 * the provided title is not allowed. An object is returned
	 * in any case.
	 */
	static public function makePage($title, $namespace, $sortkey = '', $interwiki = '') {
		$page = new SMWWikiPageValue('_wpg');
		$page->setDBkeys(array($title,$namespace,$interwiki,$sortkey));
		return $page;
	}

	/**
	 * Static function for creating a new wikipage object from a
	 * MediaWiki Title object.
	 */
	static public function makePageFromTitle($titleobject) {
		$page = new SMWWikiPageValue('_wpg');
		$page->setTitle($titleobject);
		return $page;
	}

	public function __construct($typeid) {
		parent::__construct($typeid);
		switch ($typeid) {
			case '_wpp' : case '__sup':
				$this->m_fixNamespace = SMW_NS_PROPERTY;
			break;
			case '_wpc' : case '__suc':
				$this->m_fixNamespace = NS_CATEGORY;
			break;
			case '_wpf' : case '__spf':
				$this->m_fixNamespace = SF_NS_FORM;
			break;
			default: //case '_wpg':
				$this->m_fixNamespace = NS_MAIN;
		}
	}

	protected function parseUserValue($value) {
		global $wgContLang;
		$value = ltrim(rtrim($value,' ]'),' ['); // support inputs like " [[Test]] "
		if ($value != '') {
			$this->m_title = Title::newFromText($value, $this->m_fixNamespace);
			///TODO: Escape the text so users can see any punctuation problems (bug 11666).
			if ($this->m_title === NULL) {
				wfLoadExtensionMessages('SemanticMediaWiki');
				$this->addError(wfMsgForContent('smw_notitle', $value));
			} elseif ( ($this->m_fixNamespace != NS_MAIN) &&
				 ($this->m_fixNamespace != $this->m_title->getNamespace()) ) {
				wfLoadExtensionMessages('SemanticMediaWiki');
				$this->addError(wfMsgForContent('smw_wrong_namespace', $wgContLang->getNsText($this->m_fixNamespace)));
			}
			if ($this->m_title !== NULL) {
				$this->m_textform = $this->m_title->getText();
				$this->m_dbkeyform = $this->m_title->getDBkey();
				$this->m_interwiki = $this->m_title->getInterwiki();
				$this->m_fragment = $this->m_title->getFragment();
				$this->m_prefixedtext = $this->m_title->getPrefixedText(); ///NOTE: may include interwiki prefix
				$this->m_namespace = $this->m_title->getNamespace();
				$this->m_id = false; // unset id
				if ($this->m_caption === false) {
					$this->m_caption = $value;
				}
			}
		} else {
			wfLoadExtensionMessages('SemanticMediaWiki');
			$this->addError(wfMsgForContent('smw_notitle', $value));
		}
		if ($this->m_caption === false) {
			$this->m_caption = '';
		}
	}

	protected function parseDBkeys($args) {
		$this->m_dbkeyform = $args[0];
		$this->m_namespace = array_key_exists(1,$args)?$args[1]:$this->m_fixNamespace;
		$this->m_interwiki = array_key_exists(2,$args)?$args[2]:'';
		$this->m_sortkey   = array_key_exists(3,$args)?$args[3]:'';
		$this->m_textform = str_replace('_', ' ', $this->m_dbkeyform);
		$this->m_id = false;
		$this->m_title = NULL;
		$this->m_prefixedtext = false;
		$this->m_caption = false;
		if ( ($this->m_fixNamespace != NS_MAIN) && ( $this->m_fixNamespace != $this->m_namespace) ) {
			wfLoadExtensionMessages('SemanticMediaWiki');
			$this->addError(wfMsgForContent('smw_notitle', $this->getPrefixedText()));
		}
	}

	public function getShortWikiText($linked = NULL) {
		$this->unstub();
		if ( ($linked === NULL) || ($linked === false) || ($this->m_outformat == '-') || (!$this->isValid()) || ($this->m_caption === '') ) {
			return $this->getCaption();
		} else {
			return '[[:' . str_replace("'", '&#x0027;', $this->getPrefixedText()) .
			        ($this->m_fragment?"#$this->m_fragment":'') . '|' . $this->getCaption() . ']]';
		}
	}

	public function getShortHTMLText($linker = NULL) {
		$this->unstub();
		if ( ($linker !== NULL) && ($this->m_caption !== '') && ($this->m_outformat != '-') ) { $this->getTitle(); } // init the Title object, may reveal hitherto unnoticed errors
		if ( ($linker === NULL) || (!$this->isValid()) || ($this->m_outformat == '-') || ($this->m_caption === '') ) {
			return htmlspecialchars($this->getCaption());
		} else {
			if ($this->getNamespace() == NS_MEDIA) { /// NOTE: this extra case is indeed needed
				return $linker->makeMediaLinkObj($this->getTitle(), $this->getCaption());
			} else {
				return $linker->makeLinkObj($this->getTitle(), $this->getCaption());
			}
		}
	}

	/**
	 * @note The getLong... functions of this class always hide the fragment. Fragments are currently
	 * not stored, and hence should not be shown in the Factbox (where the getLongWikiText method is used).
	 * In all other uses, values come from the store and do not have fragments anyway.
	 */
	public function getLongWikiText($linked = NULL) {
		$this->unstub();
		if (!$this->isValid()) {
			return $this->getErrorText();
		}
		if ( ($linked === NULL) || ($linked === false) || ($this->m_outformat == '-') ) {
			return $this->getPrefixedText();
		} elseif ($this->m_namespace == NS_IMAGE) { // embed images instead of linking to their page
			 return '[[' . str_replace("'", '&#x0027;', $this->getPrefixedText()) . '|' . $this->m_textform . '|frameless|border|text-top]]';
		} else { // this takes care of all other cases, esp. it is right for Media:
			return '[[:' . str_replace("'", '&#x0027;', $this->getPrefixedText()) . '|' . $this->m_textform . ']]';
		}
	}

	public function getLongHTMLText($linker = NULL) {
		$this->unstub();
		if ( ($linker !== NULL) && ($this->m_outformat != '-') ) { $this->getTitle(); } // init the Title object, may reveal hitherto unnoticed errors
		if (!$this->isValid()) {
			return $this->getErrorText();
		}
		if ( ($linker === NULL) || ($this->m_outformat == '-') ) {
			return htmlspecialchars($this->getPrefixedText());
		} else {
			if ($this->getNamespace() == NS_MEDIA) { // this extra case is really needed
				return $linker->makeMediaLinkObj($this->getTitle(), $this->m_textform);
			} else { // all others use default linking, no embedding of images here
				return $linker->makeLinkObj($this->getTitle(), $this->m_textform);
			}
		}
	}

	public function getDBkeys() {
		$this->unstub();
		return array($this->m_dbkeyform, $this->m_namespace, $this->m_interwiki, $this->getSortkey());
	}

	public function getWikiValue() {
		$this->unstub();
		if ($this->m_fixNamespace != NS_MAIN) { // no explicit namespace needed!
			return $this->getText();
		} elseif ($this->m_namespace == NS_CATEGORY) {
			// escape to enable use in links; todo: not generally required/suitable :-/
			return ':' . $this->getPrefixedText();
		} else {
			return $this->getPrefixedText();
		}
	}

	public function getHash() {
		$this->unstub();
		if ($this->isValid()) {
			return $this->getPrefixedText();
		} else {
			return implode("\t", $this->m_errors);
		}
	}

	protected function getServiceLinkParams() {
		$this->unstub();
		// Create links to mapping services based on a wiki-editable message. The parameters
		// available to the message are:
		// $1: urlencoded article name (no namespace)
		return array(rawurlencode(str_replace('_',' ',$this->m_dbkeyform)));
	}

	public function getExportData() {
		$this->unstub();
		if (!$this->isValid()) return NULL;
		switch ($this->getNamespace()) {
			case NS_MEDIA: // special handling for linking media files directly
				$file = wfFindFile( $this->getTitle() );
				if ($file) {
					//$name = $file->getFullURL();
					/// TODO: the following just emulates getFullURL() which is not yet available in MW1.11:
					$uri = $file->getUrl();
					if( substr( $uri, 0, 1 ) == '/' ) {
						global $wgServer;
						$uri = $wgServer . $uri;
					}
					return new SMWExpData(new SMWExpResource($uri, $this));
				} else { // Medialink to non-existing file :-/
					return NULL;
				}
			break;
			default: // some true wiki page
				return new SMWExpData(SMWExporter::getResourceElement($this));
			break;
		}
	}

///// special interface for wiki page values

	/**
	 * Return according Title object or NULL if no valid value was set.
	 * NULL can be returned even if this object returns TRUE for isValue(),
	 * since the latter function does not check whether MediaWiki can really
	 * make a Title out of the given data.
	 * However, isValid() will return FALSE *after* this function failed in
	 * trying to create a title.
	 */
	public function getTitle() {
		$this->unstub();
		if ( ($this->isValid()) && ($this->m_title === NULL) ) {
			if ($this->m_interwiki == '') {
				$this->m_title = Title::makeTitle($this->m_namespace, $this->m_dbkeyform);
			} else { // interwiki title objects must be built from full input texts
				$this->m_title = Title::newFromText($this->getPrefixedText());
			}
		}
		if ($this->m_title === NULL) { // should not normally happen, but anyway ...
			wfLoadExtensionMessages('SemanticMediaWiki');
			$this->addError(wfMsgForContent('smw_notitle', $wgContLang->getNsText($this->m_namespace) . ':' . $this->m_dbkeyform));
			$this->m_dbkeyform = '';
		}
		return $this->m_title;
	}

	/**
	 * Get MediaWiki's ID for this value, if any.
	 */
	public function getArticleID() {
		$this->unstub();
		if ($this->m_id === false) {
			if ($this->getTitle() !== NULL) {
				$this->m_id = $this->m_title->getArticleID();
			} else {
				$this->m_id = 0;
			}
		}
		return $this->m_id;
	}

	/**
	 * Get namespace constant for this value, if any. Otherwise
	 * return FALSE.
	 */
	public function getNamespace() {
		$this->unstub();
		if (!$this->isValid()) {
			return false;
		}
		return $this->m_namespace;
	}

	/**
	 * Get DBKey for this value. Subclasses that allow for vlaues that do not
	 * correspond to wiki pages may choose a DB key that is not a legal title
	 * DB key but rather another suitable internal ID. Thus it is not suitable
	 * to use this method in places where only MediaWiki Title keys are allowed.
	 */
	public function getDBkey() {
		$this->unstub();
		return $this->m_dbkeyform;
	}

	/// Get text label for this value.
	public function getText() {
		$this->unstub();
		return str_replace('_',' ',$this->m_dbkeyform);
	}

	/// Get the prefixed text for this value, including a localised namespace prefix.
	public function getPrefixedText() {
		global $wgContLang;
		$this->unstub();
		if ($this->m_prefixedtext === false) {
			$nstext = $wgContLang->getNSText($this->m_namespace);
			$this->m_prefixedtext = $this->m_interwiki . ($this->m_interwiki != ''?':':'') .
									$nstext . ($nstext != ''?':':'') . $this->m_textform;
		}
		return $this->m_prefixedtext;
	}

	/**
	 * Get interwiki prefix or empty string.
	 */
	public function getInterwiki() {
		$this->unstub();
		return $this->m_interwiki;
	}

	/**
	 * Get sortkey or make one as default.
	 */
	public function getSortkey() {
		$this->unstub();
		return $this->m_sortkey?$this->m_sortkey:(str_replace('_',' ',$this->m_dbkeyform));
	}

	/**
	 * Set sortkey
	 */
	public function setSortkey($sortkey) {
		$this->unstub(); // unstub first, since the stubarray also may hold a sortkey
		$this->m_sortkey = $sortkey;
	}

	/**
	 * Init this data value object based on a given Title object.
	 */
	public function setTitle($title) {
		$this->setDBkeys(array($title->getDBkey(), $title->getNamespace(), $title->getInterwiki(), ''));
		$this->m_title = $title;
	}

	/// Get the (default) caption for this value.
	protected function getCaption() {
		return $this->m_caption !== false?$this->m_caption:$this->getPrefixedText();
	}

	/**
	 * @deprecated Use setDBkeys()
	 */
	public function setValues($dbkey, $namespace, $id = false, $interwiki = '', $sortkey = '') {
		$this->setDBkeys(array($dbkey,$namespace,$interwiki,$sortkey));
	}

}

