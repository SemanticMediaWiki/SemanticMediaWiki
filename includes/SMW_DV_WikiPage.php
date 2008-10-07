<?php
/**
 * @file
 * @ingroup SMWDataValues
 */

/**
 * This datavalue implements special processing suitable for defining
 * wikipages as values of properties. This value container currently
 * behaves somewhat special in that its xsdvalue is not contained all
 * relevant information (it just gives the DB-Key, not the namespace).
 *
 * @author Nikolas Iwan
 * @author Markus Krötzsch
 * @ingroup SMWDataValues
 */
class SMWWikiPageValue extends SMWDataValue {

	private $m_value = ''; // the raw string passed to that datavalue, rough version of prefixedtext
	private $m_textform = ''; // the isolated title as text
	private $m_dbkeyform = ''; // the isolated title in DB form
	private $m_interwiki = ''; // interwiki prefix or '', actually stored in SMWSQLStore2
	private $m_sortkey = ''; // key for alphabetical sorting
	private $m_fragment = ''; // not stored, but kept for printout on page
	private $m_prefixedtext = ''; // full titletext with prefixes, including interwiki prefix
	private $m_namespace = NS_MAIN;
	private $m_id; // false if unset
	private $m_title = NULL;

	protected function parseUserValue($value) {
		$value = ltrim(rtrim($value,' ]'),' ['); // support inputs like " [[Test]] "
		if ($value != '') {
			$this->m_value = $value;
			$this->m_title = NULL;
			$this->m_dbkeyform = NULL;
			if ($this->getTitle() !== NULL) {
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
			} else {
				wfLoadExtensionMessages('SemanticMediaWiki');
				$this->addError(wfMsgForContent('smw_notitle', $value));
				# TODO: Escape the text so users can see any punctuation problems (bug 11666).
			}
		} else {
			wfLoadExtensionMessages('SemanticMediaWiki');
			$this->addError(wfMsgForContent('smw_notitle', $value));
		}
		if ($this->m_caption === false) {
			$this->m_caption = '';
		}
	}

	protected function parseXSDValue($value, $unit) { // (ignore "unit")
		// This method in its current for is not really useful for init, since the XSD value is just
		// the (dbkey) title string without the namespace.
		/// FIXME: change this to properly use a prefixed title string, in case someone wants to use this
		$this->m_stubdata = array($value,$this->m_namespace,false,'','');
	}

	protected function unstub() {
		if (is_array($this->m_stubdata)) {
			global $wgContLang;
			$this->m_dbkeyform = $this->m_stubdata[0];
			$this->m_namespace = $this->m_stubdata[1];
			$this->m_interwiki = $this->m_stubdata[3];
			$this->m_sortkey   = $this->m_stubdata[4];
			$this->m_textform = str_replace('_', ' ', $this->m_dbkeyform);
			if ($this->m_interwiki == '') {
				$this->m_title = Title::makeTitle($this->m_namespace, $this->m_dbkeyform);
				$this->m_prefixedtext = $this->m_title->getPrefixedText();
			} else { // interwiki title objects must be built from full input texts
				$nstext = $wgContLang->getNSText($this->m_namespace);
				$this->m_prefixedtext = $this->m_interwiki . ($this->m_interwiki != ''?':':'') . 
				                        $nstext . ($nstext != ''?':':'') . $this->m_textform;
				$this->m_title = Title::newFromText($this->m_prefixedtext);
			}
			$this->m_caption = $this->m_prefixedtext;
			$this->m_value = $this->m_prefixedtext;
			if ($this->m_stubdata[2] === NULL) {
				$this->m_id = 0;
				$linkCache =& LinkCache::singleton();
				$linkCache->addBadLinkObj($this->m_title); // prefill link cache, save lookups
			} elseif ($this->m_stubdata[2] === false) {
				$this->m_id = false;
			} else {
				$this->m_id = $this->m_stubdata[2];
				$linkCache =& LinkCache::singleton();
				$linkCache->addGoodLinkObj($this->m_id, $this->m_title); // prefill link cache, save lookups
			}
			$this->m_stubdata = false;
		}
	}

	public function getShortWikiText($linked = NULL) {
		$this->unstub();
		if ( ($linked === NULL) || ($linked === false) || (!$this->isValid()) || ($this->m_caption == '') ) {
			return $this->m_caption;
		} else {
			return '[[:' . str_replace("'", '&#x0027;', $this->m_prefixedtext) .
			        ($this->m_fragment?"#$this->m_fragment":'') . '|' . $this->m_caption . ']]';
		}
	}

	public function getShortHTMLText($linker = NULL) {
		$this->unstub();
		if ( ($linker === NULL) || (!$this->isValid()) || ($this->m_caption == '') ) {
			return htmlspecialchars($this->m_caption);
		} else {
			if ($this->getNamespace() == NS_MEDIA) { /// NOTE: this extra case is indeed needed
				return $linker->makeMediaLinkObj($this->getTitle(), $this->m_caption);
			} else {
				return $linker->makeLinkObj($this->getTitle(), $this->m_caption);
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
		if ( ($linked === NULL) || ($linked === false) ) {
			return $this->m_prefixedtext;
		} elseif ($this->m_namespace == NS_IMAGE) { // embed images instead of linking to their page
			 return '[[' . str_replace("'", '&#x0027;', $this->m_prefixedtext) . '|' . $this->m_textform . '|frameless|border|text-top]]';
		} else { // this takes care of all other cases, esp. it is right for Media:
			return '[[:' . str_replace("'", '&#x0027;', $this->m_prefixedtext) . '|' . $this->m_textform . ']]';
		}
	}

	public function getLongHTMLText($linker = NULL) {
		$this->unstub();
		if (!$this->isValid()) {
			return $this->getErrorText();
		}
		if ($linker === NULL) {
			return htmlspecialchars($this->m_prefixedtext);
		} else {
			if ($this->getNamespace() == NS_MEDIA) { // this extra case is really needed
				return $linker->makeMediaLinkObj($this->getTitle(), $this->m_textform);
			} else { // all others use default linking, no embedding of images here
				return $linker->makeLinkObj($this->getTitle(), $this->m_textform);
			}
		}
	}

	public function getXSDValue() {
		$this->unstub();
		return $this->m_dbkeyform;
	}

	public function getWikiValue() {
		$this->unstub();
		if ($this->m_namespace == NS_CATEGORY) {
			// escape to enable use in links; todo: not generally required/suitable :-/
			return ':' . $this->m_prefixedtext;
		} else {
			return $this->m_prefixedtext;
		}
	}

	public function getHash() {
		$this->unstub();
		if ($this->isValid()) { // assume that XSD value + unit say all
			return $this->m_prefixedtext;
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
	 */
	public function getTitle() {
		$this->unstub();
		if ($this->m_title === NULL){
			if ($this->m_dbkeyform != '') {
				$this->m_title = Title::makeTitle($this->m_namespace, $this->m_dbkeyform);
			} elseif ($this->m_value != ''){
				$this->m_title = Title::newFromText($this->m_value);
			} else {
				return NULL; //not possible to create title from empty string
			}
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
	 * Get DBKey for this value.
	 */
	public function getDBkey() {
		$this->unstub();
		return $this->m_dbkeyform;
	}

	/**
	 * Get text label for this value.
	 */
	public function getText() {
		$this->unstub();
		return str_replace('_',' ',$this->m_dbkeyform);
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
	 * Set all basic values for this datavalue to the extent these are
	 * available. Simplifies and speeds up creation from stored data.
	 */
	public function setValues($dbkey, $namespace, $id = false, $interwiki = '', $sortkey = '') {
		$this->setXSDValue($dbkey,''); // just used to trigger standard parent class methods!
		/// TODO: rethink our standard set interfaces for datavalues to make wikipage fit better with the rest
		$this->m_stubdata = array($dbkey, $namespace, $id, $interwiki, $sortkey);
	}

	/**
	 * Init this data value object based on a given Title object.
	 */
	public function setTitle($title) {
		$this->setValues($title->getDBkey(), $title->getNamespace(), false, $title->getInterwiki());
		$this->m_title = $title;
	}

}

